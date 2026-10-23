package generator

import (
	"fmt"
	"regexp"
	"strings"

	"slices"

	"github.com/iancoleman/strcase"
	"github.com/pb33f/libopenapi/datamodel/high/base"
)

var pathParamRegexp = regexp.MustCompile(`\{([^}]+)\}`)

func (g *Generator) buildServiceBlock(tagKey string, operations []*operation) string {
	className := g.displayTagName(tagKey)
	normalizeInlineResponseClassNames(className, operations)

	var buf strings.Builder
	buf.WriteString("namespace SumUp\\Services;\n\n")
	buf.WriteString("use SumUp\\HttpClient\\HttpClientInterface;\n")
	buf.WriteString("use SumUp\\HttpClient\\RequestHeaders;\n")
	buf.WriteString("use SumUp\\HttpClient\\RequestOptions;\n")
	if serviceHasRequestBody(operations) {
		buf.WriteString("use SumUp\\RequestEncoder;\n")
	}
	buf.WriteString("use SumUp\\ResponseDecoder;\n")
	buf.WriteString("\n")

	inlineResponseSchemas := collectInlineResponseSchemas(operations)
	serviceInlineSchemas := make(map[string]*base.SchemaProxy)
	for name, schema := range inlineResponseSchemas {
		serviceInlineSchemas[name] = schema
		g.collectNestedInlineServiceSchemas(name, schema, serviceInlineSchemas, make(map[*base.SchemaProxy]struct{}))
	}

	seenRequestBodies := make(map[string]struct{})
	for _, op := range operations {
		if !shouldGenerateRequestBodyClass(op) {
			continue
		}

		requestClass := requestBodyClassName(className, op)
		if _, ok := seenRequestBodies[requestClass]; ok {
			op.BodyType = requestClass
			op.BodyDocType = requestClass
			continue
		}
		seenRequestBodies[requestClass] = struct{}{}
		op.BodyType = requestClass
		op.BodyDocType = requestClass

		if op.BodySchema != nil {
			g.collectNestedInlineServiceSchemas(requestClass, op.BodySchema, serviceInlineSchemas, make(map[*base.SchemaProxy]struct{}))
		}

		if op.BodySchema != nil {
			buf.WriteString(g.buildPHPClass(requestClass, op.BodySchema, "SumUp\\Services"))
		} else {
			buf.WriteString(buildEmptyRequestBodyClass(requestClass))
		}
		buf.WriteString("\n")
	}

	if len(serviceInlineSchemas) > 0 {
		inlineNames := make([]string, 0, len(serviceInlineSchemas))
		for name := range serviceInlineSchemas {
			inlineNames = append(inlineNames, name)
		}
		slices.Sort(inlineNames)
		for _, name := range inlineNames {
			buf.WriteString(g.buildPHPClass(name, serviceInlineSchemas[name], "SumUp\\Services"))
			buf.WriteString("\n")
		}
	}

	seenParams := make(map[string]struct{})
	for _, op := range operations {
		if op == nil || !op.HasQuery {
			continue
		}
		paramsClass := queryParamsClassName(className, op)
		if _, ok := seenParams[paramsClass]; ok {
			continue
		}
		seenParams[paramsClass] = struct{}{}
		buf.WriteString(buildQueryParamsClass(paramsClass, op.QueryParams))
		buf.WriteString("\n")
	}

	fmt.Fprintf(&buf, "/**\n * Class %s\n", className)
	if description := g.tagDescription(tagKey); description != "" {
		buf.WriteString(" *\n")
		for _, line := range strings.Split(description, "\n") {
			line = strings.TrimSpace(line)
			if line == "" {
				buf.WriteString(" *\n")
				continue
			}
			buf.WriteString(" * ")
			buf.WriteString(line)
			buf.WriteString("\n")
		}
	}
	buf.WriteString(" *\n * @package SumUp\\Services\n */\n")
	fmt.Fprintf(&buf, "class %s implements SumUpService\n{\n", className)
	buf.WriteString("    /**\n")
	buf.WriteString("     * The client for the http communication.\n")
	buf.WriteString("     *\n")
	buf.WriteString("     * @var HttpClientInterface\n")
	buf.WriteString("     */\n")
	buf.WriteString("    protected HttpClientInterface $client;\n\n")
	buf.WriteString("    /**\n")
	buf.WriteString("     * The access token needed for authentication for the services.\n")
	buf.WriteString("     *\n")
	buf.WriteString("     * @var string\n")
	buf.WriteString("     */\n")
	buf.WriteString("    protected string $accessToken;\n\n")
	buf.WriteString("    /**\n")
	buf.WriteString("     * ")
	buf.WriteString(className)
	buf.WriteString(" constructor.\n")
	buf.WriteString("     *\n")
	buf.WriteString("     * @param HttpClientInterface $client\n")
	buf.WriteString("     * @param string $accessToken\n")
	buf.WriteString("     */\n")
	buf.WriteString("    public function __construct(HttpClientInterface $client, string $accessToken)\n")
	buf.WriteString("    {\n")
	buf.WriteString("        $this->client = $client;\n")
	buf.WriteString("        $this->accessToken = $accessToken;\n")
	buf.WriteString("    }\n\n")

	for idx, op := range operations {
		buf.WriteString(g.renderServiceMethod(className, op))
		if idx < len(operations)-1 {
			buf.WriteString("\n")
		}
	}

	buf.WriteString("}\n")

	return buf.String()
}

func normalizeInlineResponseClassNames(serviceClass string, operations []*operation) {
	for _, op := range operations {
		if op == nil {
			continue
		}

		methodName := op.methodName()
		if methodName == "" {
			methodName = "Operation"
		}

		baseName := fmt.Sprintf("%s%sResponse", serviceClass, strcase.ToCamel(methodName))
		for _, resp := range op.Responses {
			if resp == nil || resp.Type == nil {
				continue
			}

			inlineName := baseName
			if resp.StatusCode != "" && resp.StatusCode != "200" {
				inlineName = fmt.Sprintf("%s%s", baseName, resp.StatusCode)
			}

			renameInlineResponseType(resp.Type, inlineName)
		}
	}
}

func renameInlineResponseType(rt *responseType, inlineName string) {
	if rt == nil {
		return
	}

	if rt.InlineClassName != "" && rt.InlineSchema != nil {
		rt.InlineClassName = inlineName
		rt.ClassName = "\\SumUp\\Services\\" + inlineName
	}

	if rt.ArrayItems != nil {
		renameInlineResponseType(rt.ArrayItems, inlineName+"Item")
	}
}

func (g *Generator) renderServiceMethod(serviceClass string, op *operation) string {
	var buf strings.Builder

	methodName := op.methodName()
	if methodName == "" {
		methodName = "call"
	}

	buf.WriteString("    /**\n")
	summary := op.Summary
	if summary == "" {
		summary = op.Description
	}
	if summary == "" {
		summary = fmt.Sprintf("Call %s %s.", op.Method, op.Path)
	}
	buf.WriteString("     * ")
	buf.WriteString(summary)
	buf.WriteString("\n     *\n")

	for _, param := range op.PathParams {
		buf.WriteString("     * @param string $")
		buf.WriteString(param.VarName)
		if param.Description != "" {
			buf.WriteString(" ")
			buf.WriteString(param.Description)
		}
		buf.WriteString("\n")
	}

	if op.HasQuery {
		fmt.Fprintf(&buf, "     * @param %s|null $queryParams Optional query string parameters\n", queryParamsClassName(serviceClass, op))
	}

	if op.HasBody {
		fmt.Fprintf(&buf, "     * @param %s $body %s request payload\n", renderBodyDocType(op), renderBodyDocQualifier(op))
	}
	buf.WriteString("     * @param RequestOptions|null $requestOptions Optional typed request options\n")

	buf.WriteString("     *\n")
	fmt.Fprintf(&buf, "     * @return %s\n", renderOperationReturnDoc(op))
	buf.WriteString("     * @throws \\SumUp\\Exception\\ApiException\n")
	buf.WriteString("     * @throws \\SumUp\\Exception\\UnexpectedApiException\n")
	buf.WriteString("     * @throws \\SumUp\\Exception\\ConnectionException\n")
	buf.WriteString("     * @throws \\SumUp\\Exception\\SDKException\n")

	if op.Deprecated {
		buf.WriteString("     *\n")
		buf.WriteString("     * @deprecated\n")
	}

	buf.WriteString("     */\n")

	args := make([]string, 0, len(op.PathParams)+2)
	for _, param := range op.PathParams {
		args = append(args, "string $"+param.VarName)
	}
	if op.HasQuery {
		args = append(args, fmt.Sprintf("?%s $queryParams = null", queryParamsClassName(serviceClass, op)))
	}
	if op.HasBody {
		args = append(args, renderBodyArgument(op))
	}
	args = append(args, "?RequestOptions $requestOptions = null")

	buf.WriteString("    public function ")
	buf.WriteString(methodName)
	buf.WriteString("(")
	buf.WriteString(strings.Join(args, ", "))
	buf.WriteString(")")
	if returnType := renderOperationReturnTypeHint(op); returnType != "" {
		buf.WriteString(": ")
		buf.WriteString(returnType)
	}
	buf.WriteString("\n")
	buf.WriteString("    {\n")

	buf.WriteString(renderPathAssignment(op))

	if op.HasQuery {
		buf.WriteString("        if ($queryParams !== null) {\n")
		buf.WriteString("            $queryParamsData = [];\n")
		for _, qp := range op.QueryParams {
			if qp.VarName == "" || qp.OriginalName == "" {
				continue
			}
			fmt.Fprintf(&buf, "            if (isset($queryParams->%s)) {\n", qp.VarName)
			fmt.Fprintf(&buf, "                $queryParamsData['%s'] = $queryParams->%s;\n", qp.OriginalName, qp.VarName)
			buf.WriteString("            }\n")
		}
		buf.WriteString("            if (!empty($queryParamsData)) {\n")
		buf.WriteString("                $queryString = http_build_query($queryParamsData);\n")
		buf.WriteString("                if (!empty($queryString)) {\n")
		buf.WriteString("                    $path .= '?' . $queryString;\n")
		buf.WriteString("                }\n")
		buf.WriteString("            }\n")
		buf.WriteString("        }\n")
	}

	buf.WriteString("        $payload = [];\n")
	if op.HasBody {
		if op.BodyRequired {
			buf.WriteString(g.renderBodyEncoding("$body", op.BodyType, "        "))
		} else {
			buf.WriteString("        if ($body !== null) {\n")
			buf.WriteString(g.renderBodyEncoding("$body", op.BodyType, "            "))
			buf.WriteString("        }\n")
		}
	}

	buf.WriteString("        $headers = RequestHeaders::build($this->accessToken, $requestOptions);\n\n")
	fmt.Fprintf(&buf, "        $response = $this->client->send('%s', $path, $payload, $headers, $requestOptions);\n\n", strings.ToUpper(op.Method))
	successDescriptor := renderOperationSuccessResponseDescriptor(op)
	errorDescriptor := renderOperationErrorResponseDescriptor(op)

	switch {
	case successDescriptor != "" && errorDescriptor != "":
		fmt.Fprintf(
			&buf,
			"        return ResponseDecoder::decodeOrThrow($response, %s, %s, '%s', $path);\n",
			successDescriptor,
			errorDescriptor,
			strings.ToUpper(op.Method),
		)
	case successDescriptor != "":
		fmt.Fprintf(
			&buf,
			"        return ResponseDecoder::decodeOrThrow($response, %s, null, '%s', $path);\n",
			successDescriptor,
			strings.ToUpper(op.Method),
		)
	case errorDescriptor != "":
		fmt.Fprintf(
			&buf,
			"        return ResponseDecoder::decodeOrThrow($response, null, %s, '%s', $path);\n",
			errorDescriptor,
			strings.ToUpper(op.Method),
		)
	default:
		fmt.Fprintf(
			&buf,
			"        return ResponseDecoder::decodeOrThrow($response, null, null, '%s', $path);\n",
			strings.ToUpper(op.Method),
		)
	}
	buf.WriteString("    }\n")

	return buf.String()
}

func queryParamsClassName(serviceClass string, op *operation) string {
	methodName := op.methodName()
	if methodName == "" {
		methodName = "Operation"
	}
	if serviceClass != "" {
		return fmt.Sprintf("%s%sParams", serviceClass, strcase.ToCamel(methodName))
	}
	return fmt.Sprintf("%sParams", strcase.ToCamel(methodName))
}

func buildQueryParamsClass(className string, params []operationParam) string {
	var buf strings.Builder
	fmt.Fprintf(&buf, "/**\n * Query parameters for %s.\n *\n * @package SumUp\\Services\n */\n", className)
	fmt.Fprintf(&buf, "class %s\n{\n", className)

	for _, param := range params {
		prop := phpProperty{
			Name:        param.VarName,
			Type:        param.Type,
			DocType:     param.DocType,
			Optional:    !param.Required,
			Description: param.Description,
		}
		buf.WriteString(renderQueryParamProperty(prop))
	}

	buf.WriteString("}\n")
	return buf.String()
}

func renderQueryParamProperty(prop phpProperty) string {
	var b strings.Builder

	b.WriteString("    /**\n")
	if prop.Description != "" {
		for _, line := range strings.Split(prop.Description, "\n") {
			line = strings.TrimSpace(line)
			if line == "" {
				continue
			}
			b.WriteString("     * ")
			b.WriteString(line)
			b.WriteString("\n")
		}
	}
	b.WriteString("     *\n")
	docType := prop.DocType
	if prop.Optional {
		if !strings.Contains(docType, "null") {
			docType += "|null"
		}
	}
	fmt.Fprintf(&b, "     * @var %s\n", docType)
	b.WriteString("     */\n")

	propertyType := prop.Type
	if prop.Optional && propertyType != "mixed" && !strings.HasPrefix(propertyType, "?") {
		propertyType = "?" + propertyType
	}

	if propertyType == "" {
		propertyType = "mixed"
	}

	if prop.Optional {
		fmt.Fprintf(&b, "    public %s $%s = null;\n\n", propertyType, prop.Name)
	} else {
		fmt.Fprintf(&b, "    public %s $%s;\n\n", propertyType, prop.Name)
	}

	return b.String()
}

func collectInlineResponseSchemas(operations []*operation) map[string]*base.SchemaProxy {
	result := make(map[string]*base.SchemaProxy)
	for _, op := range operations {
		if op == nil {
			continue
		}
		for _, resp := range op.Responses {
			if resp == nil || resp.Type == nil {
				continue
			}
			collectInlineResponseSchema(resp.Type, result)
		}
	}
	return result
}

func serviceHasRequestBody(operations []*operation) bool {
	for _, op := range operations {
		if op != nil && op.HasBody {
			return true
		}
	}

	return false
}

func shouldGenerateRequestBodyClass(op *operation) bool {
	if op == nil || !op.HasBody {
		return false
	}

	if op.BodySchema == nil {
		return true
	}

	if op.BodySchema.GetReference() != "" {
		return false
	}

	if !schemaIsObject(op.BodySchema) {
		return false
	}

	return schemaShouldGenerateClass(op.BodySchema)
}

func buildEmptyRequestBodyClass(className string) string {
	var buf strings.Builder
	fmt.Fprintf(&buf, "/**\n * Request payload for %s.\n *\n * @package SumUp\\Services\n */\n", className)
	fmt.Fprintf(&buf, "class %s\n{\n", className)
	buf.WriteString("    /**\n")
	buf.WriteString("     * Create request DTO from an associative array.\n")
	buf.WriteString("     *\n")
	buf.WriteString("     * @param array<string, mixed> $data\n")
	buf.WriteString("     */\n")
	buf.WriteString("    public static function fromArray(array $data): self\n")
	buf.WriteString("    {\n")
	buf.WriteString("        return new self();\n")
	buf.WriteString("    }\n")
	buf.WriteString("}\n")
	return buf.String()
}

func requestBodyClassName(serviceClass string, op *operation) string {
	methodName := op.methodName()
	if methodName == "" {
		methodName = "Operation"
	}

	if serviceClass != "" {
		return fmt.Sprintf("%s%sRequest", serviceClass, strcase.ToCamel(methodName))
	}

	return fmt.Sprintf("%sRequest", strcase.ToCamel(methodName))
}

func collectInlineResponseSchema(rt *responseType, acc map[string]*base.SchemaProxy) {
	if rt == nil {
		return
	}
	if rt.InlineClassName != "" && rt.InlineSchema != nil {
		if _, ok := acc[rt.InlineClassName]; !ok {
			acc[rt.InlineClassName] = rt.InlineSchema
		}
	}
	if rt.ArrayItems != nil {
		collectInlineResponseSchema(rt.ArrayItems, acc)
	}
}

func (g *Generator) collectNestedInlineServiceSchemas(parentName string, schema *base.SchemaProxy, acc map[string]*base.SchemaProxy, stack map[*base.SchemaProxy]struct{}) {
	if parentName == "" || schema == nil {
		return
	}

	if _, ok := stack[schema]; ok {
		return
	}
	stack[schema] = struct{}{}
	defer delete(stack, schema)

	spec := schema.Schema()
	if spec == nil {
		return
	}

	if spec.Properties != nil {
		for propName, propSchema := range spec.Properties.FromOldest() {
			name := g.inlinePropertyClassName(parentName, propName, propSchema)
			if name != "" {
				if _, ok := acc[name]; !ok {
					acc[name] = propSchema
				}
				g.inlineSchemaNames[propSchema] = name
				g.collectNestedInlineServiceSchemas(name, propSchema, acc, stack)
			} else {
				g.collectNestedInlineServiceSchemas(parentName, propSchema, acc, stack)
			}
		}
	}

	if hasSchemaType(spec, "array") && spec.Items != nil && spec.Items.A != nil {
		itemName := g.inlineArrayItemClassName(parentName, spec.Items.A)
		if itemName != "" {
			if _, ok := acc[itemName]; !ok {
				acc[itemName] = spec.Items.A
			}
			g.inlineSchemaNames[spec.Items.A] = itemName
			g.collectNestedInlineServiceSchemas(itemName, spec.Items.A, acc, stack)
		} else {
			g.collectNestedInlineServiceSchemas(parentName, spec.Items.A, acc, stack)
		}
	}

	for _, composite := range spec.AllOf {
		g.collectNestedInlineServiceSchemas(parentName, composite, acc, stack)
	}
	for _, composite := range spec.AnyOf {
		g.collectNestedInlineServiceSchemas(parentName, composite, acc, stack)
	}
	for _, composite := range spec.OneOf {
		g.collectNestedInlineServiceSchemas(parentName, composite, acc, stack)
	}
}

func renderResponseTypeDescriptor(rt *responseType) string {
	if rt == nil {
		return "['type' => 'mixed']"
	}

	switch rt.Kind {
	case responseTypeClass:
		return fmt.Sprintf("['type' => 'class', 'class' => %s::class]", formatClassReference(rt.ClassName))
	case responseTypeArray:
		if rt.ArrayItems != nil {
			return fmt.Sprintf("['type' => 'array', 'items' => %s]", renderResponseTypeDescriptor(rt.ArrayItems))
		}
		return "['type' => 'array']"
	case responseTypeScalar:
		return fmt.Sprintf("['type' => 'scalar', 'scalar' => '%s']", rt.ScalarType)
	case responseTypeObject:
		return "['type' => 'object']"
	case responseTypeVoid:
		return "['type' => 'void']"
	case responseTypeMixed:
		return "['type' => 'mixed']"
	default:
		return "['type' => 'mixed']"
	}
}

func formatClassReference(name string) string {
	if name == "" {
		return "self"
	}

	if strings.HasPrefix(name, "\\") {
		return name
	}

	return name
}

func renderOperationReturnDoc(op *operation) string {
	if op == nil || len(op.Responses) == 0 {
		return "\\SumUp\\HttpClient\\Response"
	}

	docTypes := make([]string, 0, len(op.Responses))
	seen := make(map[string]struct{})

	for _, resp := range op.Responses {
		if resp == nil || resp.Type == nil || !resp.IsSuccess {
			continue
		}
		doc := renderResponseDocType(resp.Type)
		if doc == "" {
			continue
		}
		if _, ok := seen[doc]; ok {
			continue
		}
		seen[doc] = struct{}{}
		docTypes = append(docTypes, doc)
	}

	if len(docTypes) == 0 {
		return "\\SumUp\\HttpClient\\Response"
	}

	return strings.Join(docTypes, "|")
}

func renderOperationReturnTypeHint(op *operation) string {
	if op == nil || len(op.Responses) == 0 {
		return ""
	}

	typeHints := make([]string, 0, len(op.Responses))
	seen := make(map[string]struct{})

	for _, resp := range op.Responses {
		if resp == nil || resp.Type == nil || !resp.IsSuccess {
			continue
		}

		typeHint, ok := renderResponseTypeHint(resp.Type)
		if !ok || typeHint == "" {
			return ""
		}

		if _, exists := seen[typeHint]; exists {
			continue
		}
		seen[typeHint] = struct{}{}
		typeHints = append(typeHints, typeHint)
	}

	if len(typeHints) == 0 {
		return ""
	}

	if len(typeHints) == 1 {
		return typeHints[0]
	}

	return strings.Join(typeHints, "|")
}

func renderResponseTypeHint(rt *responseType) (string, bool) {
	if rt == nil {
		return "", false
	}

	switch rt.Kind {
	case responseTypeClass:
		return formatClassReference(rt.ClassName), true
	case responseTypeArray:
		return "array", true
	case responseTypeScalar:
		switch rt.ScalarType {
		case "string", "int", "float", "bool":
			return rt.ScalarType, true
		default:
			return "", false
		}
	case responseTypeObject:
		return "array", true
	case responseTypeVoid:
		return "null", true
	default:
		return "", false
	}
}

func renderResponseDocType(rt *responseType) string {
	if rt == nil {
		return ""
	}

	switch rt.Kind {
	case responseTypeClass:
		return formatClassReference(rt.ClassName)
	case responseTypeArray:
		itemDoc := "mixed"
		if rt.ArrayItems != nil {
			doc := renderResponseDocType(rt.ArrayItems)
			if doc != "" {
				itemDoc = doc
			}
		}
		return itemDoc + "[]"
	case responseTypeScalar:
		if rt.ScalarType != "" {
			return rt.ScalarType
		}
		return "mixed"
	case responseTypeObject:
		return "array<string, mixed>"
	case responseTypeVoid:
		return "null"
	case responseTypeMixed:
		return "mixed"
	default:
		return "mixed"
	}
}

func renderOperationSuccessResponseDescriptor(op *operation) string {
	if op == nil || len(op.Responses) == 0 {
		return ""
	}

	successResponses := make([]*operationResponse, 0, len(op.Responses))
	for _, resp := range op.Responses {
		if resp != nil && resp.IsSuccess {
			successResponses = append(successResponses, resp)
		}
	}

	if len(successResponses) == 0 {
		return ""
	}

	// Simplified approach: if there's a single 200 response with a class, just return the class name
	if len(successResponses) == 1 && successResponses[0].StatusCode == "200" {
		resp := successResponses[0]
		if resp.Type != nil && resp.Type.Kind == responseTypeClass && resp.Type.ClassName != "" {
			return fmt.Sprintf("%s::class", formatClassReference(resp.Type.ClassName))
		}
	}

	// For multiple success status codes, use descriptor array
	var buf strings.Builder
	buf.WriteString("[\n")
	for _, resp := range successResponses {
		if resp == nil || resp.Type == nil {
			continue
		}
		fmt.Fprintf(&buf, "            '%s' => %s,\n", resp.StatusCode, renderResponseTypeDescriptor(resp.Type))
	}
	buf.WriteString("        ]")

	return buf.String()
}

func renderOperationErrorResponseDescriptor(op *operation) string {
	if op == nil || len(op.Responses) == 0 {
		return ""
	}

	errorResponses := make([]*operationResponse, 0, len(op.Responses))
	for _, resp := range op.Responses {
		if resp != nil && !resp.IsSuccess {
			errorResponses = append(errorResponses, resp)
		}
	}

	if len(errorResponses) == 0 {
		return ""
	}

	var buf strings.Builder
	buf.WriteString("[\n")
	for _, resp := range errorResponses {
		if resp == nil || resp.Type == nil {
			continue
		}
		fmt.Fprintf(&buf, "            '%s' => %s,\n", resp.StatusCode, renderResponseTypeDescriptor(resp.Type))
	}
	buf.WriteString("        ]")

	return buf.String()
}

func renderPathAssignment(op *operation) string {
	if len(op.PathParams) == 0 {
		return fmt.Sprintf("        $path = '%s';\n", op.Path)
	}

	format := op.Path
	matches := pathParamRegexp.FindAllStringSubmatch(op.Path, -1)
	paramOrder := make([]string, 0, len(matches))
	for _, match := range matches {
		if len(match) < 2 {
			continue
		}
		format = strings.Replace(format, match[0], "%s", 1)
		paramOrder = append(paramOrder, match[1])
	}

	builder := strings.Builder{}
	builder.WriteString("        $path = sprintf('")
	builder.WriteString(format)
	builder.WriteString("'")
	for _, originalName := range paramOrder {
		varName := phpPropertyName(originalName)
		builder.WriteString(", rawurlencode((string) $")
		builder.WriteString(varName)
		builder.WriteString(")")
	}
	builder.WriteString(");\n")

	return builder.String()
}

func renderBodyDocQualifier(op *operation) string {
	if op != nil && op.BodyRequired {
		return "Required"
	}

	return "Optional"
}

func renderBodyDocType(op *operation) string {
	if op == nil {
		return "array<string, mixed>|null"
	}

	baseType := op.BodyDocType
	if baseType == "" {
		baseType = "array<string, mixed>"
	}

	if bodyTypeAllowsArray(op.BodyType) {
		baseType = baseType + "|array<string, mixed>"
	}

	if !op.BodyRequired && !strings.Contains(baseType, "null") {
		baseType += "|null"
	}

	return baseType
}

func renderBodyArgument(op *operation) string {
	if op == nil {
		return "?array $body = null"
	}

	baseType := op.BodyType
	if baseType == "" || baseType == "mixed" {
		baseType = "array"
	}

	if bodyTypeAllowsArray(baseType) {
		baseType = baseType + "|array"
	}

	if !op.BodyRequired {
		if baseType == "array" {
			return "?array $body = null"
		}
		if !strings.Contains(baseType, "null") {
			baseType += "|null"
		}
		return fmt.Sprintf("%s $body = null", baseType)
	}

	return fmt.Sprintf("%s $body", baseType)
}

func (g *Generator) renderBodyEncoding(bodyExpr string, bodyType string, indent string) string {
	var buf strings.Builder

	if g.bodyTypeHasGeneratedFromArray(bodyType) {
		classRef := formatClassReference(bodyType)
		fmt.Fprintf(&buf, "%s$requestBody = %s;\n", indent, bodyExpr)
		fmt.Fprintf(&buf, "%sif (is_array($requestBody)) {\n", indent)
		fmt.Fprintf(&buf, "%s    $requestBody = %s::fromArray($requestBody);\n", indent, classRef)
		fmt.Fprintf(&buf, "%s}\n", indent)
		fmt.Fprintf(&buf, "%s$payload = RequestEncoder::encode($requestBody);\n", indent)
		return buf.String()
	}

	fmt.Fprintf(&buf, "%s$payload = RequestEncoder::encode(%s);\n", indent, bodyExpr)
	return buf.String()
}

func (g *Generator) bodyTypeHasGeneratedFromArray(typeName string) bool {
	if !bodyTypeAllowsArray(typeName) {
		return false
	}

	className := phpClassBaseName(typeName)
	if className == "" {
		return false
	}

	return g.shouldGenerateConstructorForClass(className)
}

func bodyTypeAllowsArray(typeName string) bool {
	if typeName == "" {
		return false
	}
	typeName = strings.TrimPrefix(typeName, "?")
	if strings.Contains(typeName, "|") {
		return false
	}

	switch typeName {
	case "array", "mixed", "string", "int", "float", "bool", "null", "void":
		return false
	default:
		return true
	}
}
