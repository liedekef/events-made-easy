package generator

import (
	"fmt"
	"log/slog"
	"slices"
	"strconv"
	"strings"

	"github.com/iancoleman/strcase"
	"github.com/pb33f/libopenapi/datamodel/high/base"
	v3 "github.com/pb33f/libopenapi/datamodel/high/v3"

	"github.com/sumup/sumup-php/codegen/pkg/extension"
)

type operation struct {
	ID           string
	OriginalID   string
	Summary      string
	Description  string
	Method       string
	Path         string
	PathParams   []operationParam
	QueryParams  []operationParam
	HasQuery     bool
	HasBody      bool
	BodyType     string
	BodyDocType  string
	BodySchema   *base.SchemaProxy
	BodyRequired bool
	Deprecated   bool
	Responses    []*operationResponse
}

type operationParam struct {
	OriginalName string
	VarName      string
	Description  string
	Type         string
	DocType      string
	Required     bool
}

type operationResponse struct {
	StatusCode string
	Type       *responseType
	IsSuccess  bool
}

type responseTypeKind int

const (
	responseTypeUnknown responseTypeKind = iota
	responseTypeClass
	responseTypeArray
	responseTypeScalar
	responseTypeObject
	responseTypeVoid
	responseTypeMixed
)

type responseType struct {
	Kind            responseTypeKind
	ClassName       string
	ScalarType      string
	ArrayItems      *responseType
	InlineClassName string
	InlineSchema    *base.SchemaProxy
}

func (g *Generator) collectOperations() map[string][]*operation {
	result := make(map[string][]*operation)

	if g.spec == nil || g.spec.Paths == nil {
		return result
	}

	for path, pathItem := range g.spec.Paths.PathItems.FromOldest() {
		for method, op := range pathItem.GetOperations().FromOldest() {
			if op == nil {
				continue
			}

			mergedParams := make([]*v3.Parameter, 0, len(pathItem.Parameters)+len(op.Parameters))
			mergedParams = append(mergedParams, pathItem.Parameters...)
			mergedParams = append(mergedParams, op.Parameters...)

			built, err := g.buildOperation(strings.ToUpper(method), path, op, mergedParams)
			if err != nil {
				slog.Warn("unable to build operation",
					slog.String("method", method),
					slog.String("path", path),
					slog.String("error", err.Error()),
				)
				continue
			}

			if len(op.Tags) == 0 {
				slog.Warn("operation missing tags, skipping service generation",
					slog.String("operation_id", built.ID),
					slog.String("path", path),
					slog.String("method", method),
				)
				continue
			}

			for _, tag := range op.Tags {
				tagKey := normalizeTagKey(tag)
				result[tagKey] = append(result[tagKey], built)
			}
		}
	}

	for key := range result {
		slices.SortFunc(result[key], func(a, b *operation) int {
			return strings.Compare(a.ID, b.ID)
		})
	}

	return result
}

func (g *Generator) buildOperation(method, path string, op *v3.Operation, params []*v3.Parameter) (*operation, error) {
	originalOperationID := op.OperationId
	if originalOperationID == "" {
		trimmed := strings.ReplaceAll(path, "/", "_")
		if trimmed == "" {
			trimmed = "root"
		}
		originalOperationID = fmt.Sprintf("%s_%s", strings.ToLower(method), trimmed)
	}

	operationID := originalOperationID
	if ext, ok := extension.Get[map[string]any](op.Extensions, "x-codegen"); ok {
		if methodName, ok := ext["method_name"]; ok {
			if methodStr, ok := methodName.(string); ok && methodStr != "" {
				operationID = methodStr
			}
		}
	}

	pathParams := make([]operationParam, 0)
	queryParams := make([]operationParam, 0)
	for _, param := range params {
		if param == nil {
			continue
		}

		switch param.In {
		case "path":
			pathParams = append(pathParams, operationParam{
				OriginalName: param.Name,
				VarName:      phpPropertyName(param.Name),
				Description:  param.Description,
			})
		case "query":
			required := false
			if param.Required != nil {
				required = *param.Required
			}
			paramType, paramDocType := g.resolvePHPType(param.Schema, "SumUp\\Services", "", "")
			queryParams = append(queryParams, operationParam{
				OriginalName: param.Name,
				VarName:      phpPropertyName(param.Name),
				Description:  param.Description,
				Type:         paramType,
				DocType:      paramDocType,
				Required:     required,
			})
		}
	}

	hasBody := op.RequestBody != nil
	bodyType, bodyDocType, bodyRequired, bodySchema := g.resolveOperationBody(op)
	deprecated := false
	if op.Deprecated != nil {
		deprecated = *op.Deprecated
	}

	return &operation{
		ID:           operationID,
		OriginalID:   originalOperationID,
		Summary:      strings.TrimSpace(op.Summary),
		Description:  strings.TrimSpace(op.Description),
		Method:       method,
		Path:         path,
		PathParams:   pathParams,
		QueryParams:  queryParams,
		HasQuery:     len(queryParams) > 0,
		HasBody:      hasBody,
		BodyType:     bodyType,
		BodyDocType:  bodyDocType,
		BodySchema:   bodySchema,
		BodyRequired: bodyRequired,
		Deprecated:   deprecated,
		Responses:    g.collectOperationResponses(op, originalOperationID),
	}, nil
}

func (g *Generator) resolveOperationBody(op *v3.Operation) (string, string, bool, *base.SchemaProxy) {
	if op == nil || op.RequestBody == nil {
		return "", "", false, nil
	}

	required := op.RequestBody.Required != nil && *op.RequestBody.Required

	var schema *base.SchemaProxy
	if op.RequestBody.Content != nil {
		if mediaType, ok := op.RequestBody.Content.Get("application/json"); ok && mediaType != nil {
			schema = mediaType.Schema
		}
		if schema == nil {
			for _, mediaType := range op.RequestBody.Content.FromOldest() {
				if mediaType != nil && mediaType.Schema != nil {
					schema = mediaType.Schema
					break
				}
			}
		}
	}

	if schema == nil {
		return "array", "array<string, mixed>", required, nil
	}

	bodyType, bodyDocType := g.resolvePHPType(schema, "SumUp\\Services", "", "")
	if bodyType == "" {
		bodyType = "array"
	}
	if bodyDocType == "" {
		bodyDocType = "array<string, mixed>"
	}

	return bodyType, bodyDocType, required, schema
}

func (op *operation) methodName() string {
	if op == nil {
		return ""
	}

	if op.ID == "" {
		return ""
	}

	return strcase.ToLowerCamel(op.ID)
}

func (g *Generator) collectOperationResponses(op *v3.Operation, operationID string) []*operationResponse {
	if op == nil || op.Responses == nil || op.Responses.Codes.Len() == 0 {
		return nil
	}

	responses := make([]*operationResponse, 0, op.Responses.Codes.Len())

	for status, response := range op.Responses.Codes.FromOldest() {
		respType := g.responseTypeForResponse(response, "SumUp\\Services", operationID, status)
		if respType == nil {
			continue
		}

		statusCode, err := strconv.Atoi(status)
		if err != nil {
			continue
		}

		responses = append(responses, &operationResponse{
			StatusCode: status,
			Type:       respType,
			IsSuccess:  statusCode >= 200 && statusCode < 300,
		})
	}

	return responses
}

func (g *Generator) responseTypeForResponse(resp *v3.Response, currentNamespace string, operationID string, statusCode string) *responseType {
	if resp == nil {
		return &responseType{Kind: responseTypeVoid}
	}

	if resp.Content == nil || resp.Content.Len() == 0 {
		return &responseType{Kind: responseTypeVoid}
	}

	var schema *base.SchemaProxy

	if mediaType, ok := resp.Content.Get("application/problem+json"); ok {
		schema = mediaType.Schema
	} else if mediaType, ok := resp.Content.Get("application/json"); ok {
		schema = mediaType.Schema
	}

	if schema == nil {
		return &responseType{Kind: responseTypeVoid}
	}

	return g.buildResponseType(schema, currentNamespace, inlineResponseClassName(operationID, statusCode))
}

func (g *Generator) buildResponseType(schema *base.SchemaProxy, currentNamespace string, inlineBaseName string) *responseType {
	if schema == nil {
		return nil
	}

	if ref := schema.GetReference(); ref != "" {
		if !schemaIsObject(schema) {
			return g.buildResponseTypeFromSpec(schema.Schema(), currentNamespace)
		}

		if !schemaShouldGenerateClass(schema) {
			return &responseType{Kind: responseTypeObject}
		}

		name := schemaClassName(schema)
		namespace := g.schemaNamespaces[name]
		typeName := name
		if namespace != "" && namespace != currentNamespace {
			typeName = fmt.Sprintf("\\%s\\%s", namespace, name)
		}

		return &responseType{
			Kind:      responseTypeClass,
			ClassName: typeName,
		}
	}

	spec := schema.Schema()
	if spec == nil {
		return nil
	}

	if hasSchemaType(spec, "array") {
		var itemType *responseType
		if spec.Items != nil && spec.Items.A != nil {
			itemInlineName := ""
			if inlineBaseName != "" {
				itemInlineName = inlineBaseName + "Item"
			}
			itemType = g.buildResponseType(spec.Items.A, currentNamespace, itemInlineName)
		}
		return &responseType{
			Kind:       responseTypeArray,
			ArrayItems: itemType,
		}
	}

	if hasSchemaType(spec, "object") && inlineBaseName != "" && schemaShouldGenerateClass(schema) {
		return &responseType{
			Kind:            responseTypeClass,
			ClassName:       fmt.Sprintf("\\SumUp\\Services\\%s", inlineBaseName),
			InlineClassName: inlineBaseName,
			InlineSchema:    schema,
		}
	}

	return g.buildResponseTypeFromSpec(spec, currentNamespace)
}

func (g *Generator) buildResponseTypeFromSpec(spec *base.Schema, currentNamespace string) *responseType {
	if spec == nil {
		return nil
	}

	if len(spec.Enum) > 0 {
		return &responseType{
			Kind:       responseTypeScalar,
			ScalarType: "string",
		}
	}

	switch {
	case hasSchemaType(spec, "string"):
		return &responseType{Kind: responseTypeScalar, ScalarType: "string"}
	case hasSchemaType(spec, "integer"):
		return &responseType{Kind: responseTypeScalar, ScalarType: "int"}
	case hasSchemaType(spec, "number"):
		return &responseType{Kind: responseTypeScalar, ScalarType: "float"}
	case hasSchemaType(spec, "boolean"):
		return &responseType{Kind: responseTypeScalar, ScalarType: "bool"}
	case hasSchemaType(spec, "array"):
		var itemType *responseType
		if spec.Items != nil && spec.Items.A != nil {
			itemType = g.buildResponseType(spec.Items.A, currentNamespace, "")
		}
		return &responseType{
			Kind:       responseTypeArray,
			ArrayItems: itemType,
		}
	case hasSchemaType(spec, "object"):
		return &responseType{Kind: responseTypeObject}
	default:
	}

	if len(spec.OneOf) > 0 || len(spec.AllOf) > 0 || len(spec.AnyOf) > 0 {
		return &responseType{Kind: responseTypeMixed}
	}

	return &responseType{Kind: responseTypeMixed}
}

func inlineResponseClassName(operationID string, statusCode string) string {
	if operationID == "" {
		return ""
	}

	base := strcase.ToCamel(operationID) + "Response"
	if statusCode != "" && statusCode != "200" {
		base += statusCode
	}

	return base
}
