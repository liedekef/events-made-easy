package generator

import (
	"fmt"
	"slices"
	"strconv"
	"strings"

	"github.com/pb33f/go-yaml"
	"github.com/pb33f/libopenapi/datamodel/high/base"
	v3 "github.com/pb33f/libopenapi/datamodel/high/v3"
)

const (
	sampleCatalogSchemaVersion = 1
	sdkPackage                 = "sumup/sumup-php"
)

// SampleCatalog is the versioned JSON contract consumed by documentation sites.
type SampleCatalog struct {
	SchemaVersion  int      `json:"schemaVersion"`
	Language       string   `json:"language"`
	SDK            SDK      `json:"sdk"`
	OpenAPIVersion string   `json:"openAPIVersion"`
	Samples        []Sample `json:"samples"`
}

// SDK identifies the package used by every generated sample.
type SDK struct {
	Module  string `json:"module"`
	Version string `json:"version"`
}

// Sample is a complete PHP program for one OpenAPI operation example.
type Sample struct {
	ID          string `json:"id"`
	OperationID string `json:"operationId"`
	Example     string `json:"example,omitempty"`
	Summary     string `json:"summary,omitempty"`
	Description string `json:"description,omitempty"`
	HTTPMethod  string `json:"httpMethod"`
	Path        string `json:"path"`
	Source      string `json:"sample"`
}

type requestExample struct {
	name        string
	summary     string
	description string
	value       any
	provided    bool
}

// Samples builds a deterministic catalog of syntax-valid PHP examples.
func (g *Generator) Samples(sdkVersion string) (*SampleCatalog, error) {
	if g.spec == nil {
		return nil, fmt.Errorf("missing specs: call Load to load the specs first")
	}
	if g.spec.Info == nil {
		return nil, fmt.Errorf("missing specs info: call Load to load the specs first")
	}
	if g.spec.Paths == nil || g.spec.Paths.PathItems == nil {
		return nil, fmt.Errorf("missing specs paths: call Load to load the specs first")
	}

	paths := make([]string, 0, g.spec.Paths.PathItems.Len())
	for path := range g.spec.Paths.PathItems.FromOldest() {
		paths = append(paths, path)
	}
	slices.Sort(paths)

	samples := make([]Sample, 0)
	for _, path := range paths {
		pathItem, ok := g.spec.Paths.PathItems.Get(path)
		if !ok || pathItem == nil || pathItem.IsReference() {
			continue
		}

		operations := pathItem.GetOperations()
		methods := slices.Collect(operations.KeysFromOldest())
		slices.Sort(methods)
		for _, method := range methods {
			specOperation, ok := operations.Get(method)
			if !ok || specOperation == nil {
				continue
			}
			if specOperation.OperationId == "" {
				return nil, fmt.Errorf("missing operation id for %s %s", strings.ToUpper(method), path)
			}
			if len(specOperation.Tags) == 0 {
				return nil, fmt.Errorf("missing tag for operation %q", specOperation.OperationId)
			}

			params := make([]*v3.Parameter, 0, len(pathItem.Parameters)+len(specOperation.Parameters))
			params = append(params, pathItem.Parameters...)
			params = append(params, specOperation.Parameters...)
			built, err := g.buildOperation(strings.ToUpper(method), path, specOperation, params)
			if err != nil {
				return nil, fmt.Errorf("build operation %q: %w", specOperation.OperationId, err)
			}

			operationSamples, err := g.samplesForOperation(
				g.displayTagName(normalizeTagKey(specOperation.Tags[0])),
				strings.ToUpper(method),
				path,
				specOperation,
				params,
				built,
			)
			if err != nil {
				return nil, fmt.Errorf("generate samples for %q: %w", specOperation.OperationId, err)
			}
			samples = append(samples, operationSamples...)
		}
	}

	slices.SortFunc(samples, func(a, b Sample) int {
		return strings.Compare(a.ID, b.ID)
	})

	return &SampleCatalog{
		SchemaVersion: sampleCatalogSchemaVersion,
		Language:      "php",
		SDK: SDK{
			Module:  sdkPackage,
			Version: sdkVersion,
		},
		OpenAPIVersion: strings.TrimSpace(g.spec.Info.Version),
		Samples:        samples,
	}, nil
}

func (g *Generator) samplesForOperation(
	serviceClass string,
	httpMethod string,
	path string,
	specOperation *v3.Operation,
	params []*v3.Parameter,
	built *operation,
) ([]Sample, error) {
	examples := requestExamples(specOperation)
	samples := make([]Sample, 0, len(examples))
	for _, example := range examples {
		source, err := g.renderSample(serviceClass, specOperation, params, built, example)
		if err != nil {
			return nil, err
		}

		id := specOperation.OperationId
		if example.name != "" {
			id += "." + example.name
		}
		summary := strings.TrimSpace(specOperation.Summary)
		if example.summary != "" {
			summary = strings.TrimSpace(example.summary)
		}
		description := strings.TrimSpace(specOperation.Description)
		if example.description != "" {
			description = strings.TrimSpace(example.description)
		}

		samples = append(samples, Sample{
			ID:          id,
			OperationID: specOperation.OperationId,
			Example:     example.name,
			Summary:     summary,
			Description: description,
			HTTPMethod:  httpMethod,
			Path:        path,
			Source:      source,
		})
	}

	return samples, nil
}

func requestExamples(operation *v3.Operation) []requestExample {
	mediaType := requestJSONMediaType(operation)
	if mediaType == nil {
		return []requestExample{{}}
	}

	if mediaType.Examples != nil && mediaType.Examples.Len() > 0 {
		names := slices.Collect(mediaType.Examples.KeysFromOldest())
		slices.Sort(names)
		examples := make([]requestExample, 0, len(names))
		for _, name := range names {
			example, ok := mediaType.Examples.Get(name)
			if !ok || example == nil {
				continue
			}
			value, provided := decodeNode(example.Value)
			examples = append(examples, requestExample{
				name:        name,
				summary:     example.Summary,
				description: example.Description,
				value:       value,
				provided:    provided,
			})
		}
		if len(examples) > 0 {
			return examples
		}
	}

	if value, provided := decodeNode(mediaType.Example); provided {
		return []requestExample{{value: value, provided: true}}
	}
	if value, provided := schemaExample(mediaType.Schema); provided {
		return []requestExample{{value: value, provided: true}}
	}
	return []requestExample{{}}
}

func requestJSONMediaType(operation *v3.Operation) *v3.MediaType {
	if operation == nil || operation.RequestBody == nil || operation.RequestBody.Content == nil {
		return nil
	}
	if mediaType, ok := operation.RequestBody.Content.Get("application/json"); ok {
		return mediaType
	}
	for _, mediaType := range operation.RequestBody.Content.FromOldest() {
		if mediaType != nil {
			return mediaType
		}
	}
	return nil
}

func (g *Generator) renderSample(
	serviceClass string,
	specOperation *v3.Operation,
	params []*v3.Parameter,
	built *operation,
	example requestExample,
) (string, error) {
	var body strings.Builder
	body.WriteString("<?php\n\ndeclare(strict_types=1);\n\n")
	body.WriteString("require __DIR__ . '/vendor/autoload.php';\n\n")
	body.WriteString("$sumup = new \\SumUp\\SumUp('sup_sk_your_api_key');\n")

	usesQueryParams := false
	if built.HasQuery {
		assignments := make([]string, 0, len(built.QueryParams))
		for _, queryParam := range built.QueryParams {
			parameter := findParameter(params, queryParam.OriginalName, "query")
			value, provided := parameterExample(parameter)
			if !provided && queryParam.Required {
				value = exampleForSchema(parameterSchema(parameter), make(map[*base.SchemaProxy]struct{}))
				provided = true
			}
			if !provided {
				continue
			}
			assignments = append(assignments, fmt.Sprintf(
				"$queryParams->%s = %s;\n",
				queryParam.VarName,
				renderPHPValue(value, 0),
			))
		}
		if len(assignments) == 0 && len(built.QueryParams) > 0 {
			queryParam := built.QueryParams[0]
			parameter := findParameter(params, queryParam.OriginalName, "query")
			value := placeholderForParameter(
				built.OriginalID+"_"+queryParam.OriginalName,
				parameterSchema(parameter),
			)
			assignments = append(assignments, fmt.Sprintf(
				"$queryParams->%s = %s;\n",
				queryParam.VarName,
				renderPHPValue(value, 0),
			))
		}
		if len(assignments) > 0 {
			usesQueryParams = true
			paramsClass := queryParamsClassName(serviceClass, built)
			fmt.Fprintf(&body, "\n$queryParams = new \\SumUp\\Services\\%s();\n", paramsClass)
			for _, assignment := range assignments {
				body.WriteString(assignment)
			}
		}
	}

	if built.HasBody {
		value := example.value
		if !example.provided {
			mediaType := requestJSONMediaType(specOperation)
			if mediaType != nil {
				value = exampleForSchema(mediaType.Schema, make(map[*base.SchemaProxy]struct{}))
			}
		}
		body.WriteString("\n$body = ")
		body.WriteString(renderPHPValue(value, 0))
		body.WriteString(";\n")
	}

	args := make([]string, 0, len(built.PathParams)+2)
	for _, pathParam := range built.PathParams {
		parameter := findParameter(params, pathParam.OriginalName, "path")
		value, provided := parameterExample(parameter)
		if !provided {
			value = placeholderForParameter(pathParam.OriginalName, parameterSchema(parameter))
		}
		args = append(args, renderPHPValue(value, 1))
	}
	if usesQueryParams {
		args = append(args, "$queryParams")
	}
	if built.HasBody {
		args = append(args, "$body")
	}

	body.WriteString("\n$result = $sumup->")
	body.WriteString(phpPropertyName(serviceClass))
	body.WriteString("()->")
	body.WriteString(built.methodName())
	body.WriteString("(")
	if len(args) > 0 {
		body.WriteString("\n")
		for _, argument := range args {
			body.WriteString("    ")
			body.WriteString(argument)
			body.WriteString(",\n")
		}
	}
	body.WriteString(");\n\nvar_dump($result);\n")
	return body.String(), nil
}

func findParameter(params []*v3.Parameter, name, location string) *v3.Parameter {
	for _, parameter := range params {
		if parameter != nil && parameter.Name == name && parameter.In == location {
			return parameter
		}
	}
	return nil
}

func parameterSchema(parameter *v3.Parameter) *base.SchemaProxy {
	if parameter == nil {
		return nil
	}
	return parameter.Schema
}

func parameterExample(parameter *v3.Parameter) (any, bool) {
	if parameter == nil {
		return nil, false
	}
	if value, provided := decodeNode(parameter.Example); provided {
		return value, true
	}
	if parameter.Examples != nil && parameter.Examples.Len() > 0 {
		names := slices.Collect(parameter.Examples.KeysFromOldest())
		slices.Sort(names)
		for _, name := range names {
			example, ok := parameter.Examples.Get(name)
			if ok && example != nil {
				if value, provided := decodeNode(example.Value); provided {
					return value, true
				}
			}
		}
	}
	return schemaExample(parameter.Schema)
}

func schemaExample(schema *base.SchemaProxy) (any, bool) {
	if schema == nil || schema.Schema() == nil {
		return nil, false
	}
	spec := schema.Schema()
	if value, provided := decodeNode(spec.Example); provided {
		return value, true
	}
	if value, provided := decodeNode(spec.Default); provided {
		return value, true
	}
	if len(spec.Examples) > 0 {
		if value, provided := decodeNode(spec.Examples[0]); provided {
			return value, true
		}
	}
	if len(spec.Enum) > 0 {
		return decodeNode(spec.Enum[0])
	}
	return nil, false
}

func decodeNode(node *yaml.Node) (any, bool) {
	if node == nil {
		return nil, false
	}
	var value any
	if err := node.Decode(&value); err != nil {
		return nil, false
	}
	return value, true
}

func exampleForSchema(schema *base.SchemaProxy, visited map[*base.SchemaProxy]struct{}) any {
	if value, provided := schemaExample(schema); provided {
		return value
	}
	if schema == nil || schema.Schema() == nil {
		return nil
	}
	if _, ok := visited[schema]; ok {
		return nil
	}
	visited[schema] = struct{}{}
	defer delete(visited, schema)

	spec := schema.Schema()
	if len(spec.OneOf) > 0 {
		return exampleForSchema(spec.OneOf[0], visited)
	}
	if len(spec.AnyOf) > 0 {
		return exampleForSchema(spec.AnyOf[0], visited)
	}
	if len(spec.AllOf) > 0 || hasSchemaType(spec, "object") || spec.Properties != nil {
		value := make(map[string]any)
		for _, composite := range spec.AllOf {
			if nested, ok := exampleForSchema(composite, visited).(map[string]any); ok {
				for key, item := range nested {
					value[key] = item
				}
			}
		}
		required := make(map[string]struct{}, len(spec.Required))
		for _, name := range spec.Required {
			required[name] = struct{}{}
		}
		if spec.Properties != nil {
			for name, property := range spec.Properties.FromOldest() {
				_, isRequired := required[name]
				propertyValue, provided := schemaExample(property)
				if !isRequired && !provided {
					continue
				}
				if !provided {
					propertyValue = exampleForSchema(property, visited)
				}
				value[name] = propertyValue
			}
		}
		return value
	}
	if hasSchemaType(spec, "array") {
		if spec.Items != nil && spec.Items.A != nil {
			return []any{exampleForSchema(spec.Items.A, visited)}
		}
		return []any{}
	}
	if hasSchemaType(spec, "boolean") {
		return true
	}
	if hasSchemaType(spec, "integer") {
		return 1
	}
	if hasSchemaType(spec, "number") {
		return 10.0
	}
	if hasSchemaType(spec, "string") {
		switch spec.Format {
		case "date":
			return "2026-01-01"
		case "date-time":
			return "2026-01-01T12:00:00Z"
		case "email":
			return "developer@example.com"
		case "uuid":
			return "00000000-0000-4000-8000-000000000000"
		case "uri", "url":
			return "https://example.com"
		default:
			return "example"
		}
	}
	return nil
}

func placeholderForParameter(name string, schema *base.SchemaProxy) any {
	lowerName := strings.ToLower(name)
	switch {
	case strings.Contains(lowerName, "merchant"):
		return "M123456789"
	case strings.Contains(lowerName, "checkout"):
		return "checkout-id"
	case strings.Contains(lowerName, "transaction"):
		return "transaction-id"
	case strings.Contains(lowerName, "customer"):
		return "customer-id"
	case strings.Contains(lowerName, "reader"):
		return "reader-id"
	case strings.HasSuffix(lowerName, "_id"):
		return strings.TrimSuffix(strings.ReplaceAll(lowerName, "_", "-"), "-id") + "-id"
	default:
		return exampleForSchema(schema, make(map[*base.SchemaProxy]struct{}))
	}
}

func renderPHPValue(value any, indent int) string {
	switch typed := value.(type) {
	case nil:
		return "null"
	case string:
		return "'" + strings.ReplaceAll(strings.ReplaceAll(typed, "\\", "\\\\"), "'", "\\'") + "'"
	case bool:
		return strconv.FormatBool(typed)
	case int:
		return strconv.Itoa(typed)
	case int8:
		return strconv.FormatInt(int64(typed), 10)
	case int16:
		return strconv.FormatInt(int64(typed), 10)
	case int32:
		return strconv.FormatInt(int64(typed), 10)
	case int64:
		return strconv.FormatInt(typed, 10)
	case uint:
		return strconv.FormatUint(uint64(typed), 10)
	case uint8:
		return strconv.FormatUint(uint64(typed), 10)
	case uint16:
		return strconv.FormatUint(uint64(typed), 10)
	case uint32:
		return strconv.FormatUint(uint64(typed), 10)
	case uint64:
		return strconv.FormatUint(typed, 10)
	case float32:
		return strconv.FormatFloat(float64(typed), 'f', -1, 32)
	case float64:
		return strconv.FormatFloat(typed, 'f', -1, 64)
	case []any:
		if len(typed) == 0 {
			return "[]"
		}
		var result strings.Builder
		result.WriteString("[\n")
		for _, item := range typed {
			result.WriteString(strings.Repeat("    ", indent+1))
			result.WriteString(renderPHPValue(item, indent+1))
			result.WriteString(",\n")
		}
		result.WriteString(strings.Repeat("    ", indent))
		result.WriteString("]")
		return result.String()
	case map[string]any:
		if len(typed) == 0 {
			return "[]"
		}
		keys := make([]string, 0, len(typed))
		for key := range typed {
			keys = append(keys, key)
		}
		slices.Sort(keys)
		var result strings.Builder
		result.WriteString("[\n")
		for _, key := range keys {
			result.WriteString(strings.Repeat("    ", indent+1))
			result.WriteString(renderPHPValue(key, indent+1))
			result.WriteString(" => ")
			result.WriteString(renderPHPValue(typed[key], indent+1))
			result.WriteString(",\n")
		}
		result.WriteString(strings.Repeat("    ", indent))
		result.WriteString("]")
		return result.String()
	case map[any]any:
		normalized := make(map[string]any, len(typed))
		for key, item := range typed {
			normalized[fmt.Sprint(key)] = item
		}
		return renderPHPValue(normalized, indent)
	default:
		return renderPHPValue(fmt.Sprint(value), indent)
	}
}
