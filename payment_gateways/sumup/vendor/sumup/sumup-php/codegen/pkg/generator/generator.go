package generator

import (
	"bytes"
	"fmt"
	"log/slog"
	"maps"
	"os"
	"path/filepath"
	"slices"
	"strings"

	"github.com/pb33f/libopenapi/datamodel/high/base"
	v3 "github.com/pb33f/libopenapi/datamodel/high/v3"
)

const (
	sharedTagKey         = "__shared"
	sharedTagDisplayName = "Shared"
	sharedTagNamespace   = "SumUp\\Shared"
	typesTagKey          = "__types"
	typesTagDisplayName  = "Types"
	typesNamespace       = "SumUp\\Types"
)

// Config defines generator options.
type Config struct {
	// Out is the output directory.
	Out string
}

// Generator orchestrates the SDK generation.
type Generator struct {
	cfg Config

	spec *v3.Document

	tagLookup map[string]*base.Tag

	// inlineSchemaNames tracks deterministic generated names for inline object schemas.
	inlineSchemaNames map[*base.SchemaProxy]string

	// schemasByTag maps normalized tag names to schemas they own.
	schemasByTag map[string][]*base.SchemaProxy

	// schemaNamespaces tracks where a schema is defined so we can reference it.
	schemaNamespaces map[string]string

	operationsByTag map[string][]*operation

	// enumsByTag maps normalized tag names to enums they own.
	enumsByTag map[string][]enumDefinition

	// enumNamespaces tracks where an enum is defined so we can reference it.
	enumNamespaces map[string]string

	// requestClassNames tracks schema classes used as OpenAPI request bodies.
	requestClassNames map[string]struct{}
}

type enumDefinition struct {
	Name        string
	Description string
	Values      []string
	Type        string // "string" or "int"
}

// New creates a new Generator instance.
func New(cfg Config) *Generator {
	return &Generator{
		cfg:               cfg,
		inlineSchemaNames: make(map[*base.SchemaProxy]string),
		requestClassNames: make(map[string]struct{}),
	}
}

// Load ingests the OpenAPI spec and prepares it for code generation.
func (g *Generator) Load(spec *v3.Document) error {
	if spec == nil {
		return fmt.Errorf("nil spec")
	}

	normalizeSchemaAnnotations(spec)
	g.spec = spec
	g.tagLookup = make(map[string]*base.Tag)
	for _, tag := range spec.Tags {
		if tag == nil {
			continue
		}
		g.tagLookup[normalizeTagKey(tag.Name)] = tag
	}

	usage := g.collectSchemaUsage()
	g.schemasByTag, g.schemaNamespaces = g.assignSchemasToTags(usage)
	g.operationsByTag = g.collectOperations()
	g.collectRequestClassNames()
	g.enumsByTag, g.enumNamespaces = g.collectEnums()

	return nil
}

// Build generates the SDK in the configured destination.
func (g *Generator) Build() error {
	if g.spec == nil {
		return fmt.Errorf("missing specs: call Load to load the specs first")
	}

	tagSet := make(map[string]struct{})
	for tag := range g.schemasByTag {
		tagSet[tag] = struct{}{}
	}
	for tag := range g.operationsByTag {
		tagSet[tag] = struct{}{}
	}

	tagKeys := slices.Collect(maps.Keys(tagSet))
	slices.Sort(tagKeys)

	for _, tagKey := range tagKeys {
		schemas := g.schemasByTag[tagKey]
		operations := g.operationsByTag[tagKey]

		if len(schemas) == 0 && len(operations) == 0 {
			continue
		}

		if tagKey == typesTagKey {
			if err := g.writeTypeModels(schemas); err != nil {
				return err
			}
			continue
		}

		if err := g.writeTagFile(tagKey, schemas, operations); err != nil {
			return err
		}
	}

	slog.Info("models generated", slog.Int("tags", len(tagKeys)))

	// Skip SumUp class generation to avoid breaking existing code
	// TODO: Fix Authorization service dependency before re-enabling
	// if err := g.writeSumUpClass(); err != nil {
	// 	return err
	// }

	if err := g.writeApiVersion(); err != nil {
		return err
	}

	return nil
}

func (g *Generator) writeTagFile(tagKey string, schemas []*base.SchemaProxy, operations []*operation) error {
	tagName := g.displayTagName(tagKey)
	namespace := g.namespaceForTag(tagKey)
	includeService := g.shouldIncludeService(tagKey, operations)

	dir := filepath.Join(g.cfg.Out, tagName)
	if err := os.MkdirAll(dir, os.ModePerm); err != nil {
		return fmt.Errorf("create tag directory: %w", err)
	}

	filename := filepath.Join(dir, fmt.Sprintf("%s.php", tagName))
	f, err := os.OpenFile(filename, os.O_CREATE|os.O_WRONLY|os.O_TRUNC, 0o644)
	if err != nil {
		return fmt.Errorf("open %q: %w", filename, err)
	}
	defer func() {
		_ = f.Close()
	}()

	var buf bytes.Buffer
	buf.WriteString("<?php\n\ndeclare(strict_types=1);\n\n")
	fmt.Fprintf(&buf, "namespace %s;\n", namespace)

	hasEnums := false
	if enums, ok := g.enumsByTag[tagKey]; ok && len(enums) > 0 {
		hasEnums = true
	}
	hasSchemas := len(schemas) > 0
	hasTagContent := hasEnums || hasSchemas
	if hasTagContent {
		buf.WriteString("\n")
	}

	// Write enums first if any exist for this tag
	if enums, ok := g.enumsByTag[tagKey]; ok && len(enums) > 0 {
		for idx, enum := range enums {
			enumCode := g.buildPHPEnum(enum)
			buf.WriteString(enumCode)
			if idx < len(enums)-1 || len(schemas) > 0 {
				buf.WriteString("\n")
			}
		}
	}

	// Write classes
	for idx, schema := range schemas {
		className := schemaClassName(schema)
		classCode := g.buildPHPClass(className, schema, namespace)
		buf.WriteString(classCode)
		if idx < len(schemas)-1 {
			buf.WriteString("\n")
		}
	}

	if includeService {
		buf.WriteString("\n")
		buf.WriteString(g.buildServiceBlock(tagKey, operations))
	}

	if _, err := f.Write(buf.Bytes()); err != nil {
		return fmt.Errorf("write file %q: %w", filename, err)
	}

	if includeService {
		if err := g.removeLegacyServiceFile(tagName); err != nil {
			return err
		}
	}

	enumCount := 0
	if enums, ok := g.enumsByTag[tagKey]; ok {
		enumCount = len(enums)
	}

	serviceCount := 0
	if includeService {
		serviceCount = 1
	}

	slog.Info("generated tag file",
		slog.String("tag", tagName),
		slog.String("namespace", namespace),
		slog.String("file", filename),
		slog.Int("classes", len(schemas)),
		slog.Int("enums", enumCount),
		slog.Int("services", serviceCount),
	)

	return nil
}

func (g *Generator) removeLegacyServiceFile(className string) error {
	servicesDir := filepath.Join(g.cfg.Out, "Services")
	filename := filepath.Join(servicesDir, fmt.Sprintf("%s.php", className))
	if err := os.Remove(filename); err != nil && !os.IsNotExist(err) {
		return fmt.Errorf("remove legacy service file %q: %w", filename, err)
	}
	return nil
}

func (g *Generator) buildPHPClass(name string, schema *base.SchemaProxy, currentNamespace string) string {
	var buf strings.Builder
	description := ""
	if schema.Schema() != nil {
		description = schema.Schema().Description
	}

	if description != "" {
		buf.WriteString("/**\n")
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
		buf.WriteString(" */\n")
	}

	fmt.Fprintf(&buf, "class %s\n{\n", name)

	properties := g.schemaProperties(schema, currentNamespace, name)
	if len(properties) == 0 {
		buf.WriteString("}\n")
		return buf.String()
	}

	for _, prop := range properties {
		propCode := g.renderProperty(prop)
		buf.WriteString(propCode)
	}

	if g.shouldGenerateConstructorForClass(name) {
		buf.WriteString(g.buildRequestConstructor(properties))
	}

	buf.WriteString("}\n")
	return buf.String()
}

func (g *Generator) buildRequestConstructor(properties []phpProperty) string {
	var buf strings.Builder

	buf.WriteString("    /**\n")
	buf.WriteString("     * Create request DTO.\n")
	buf.WriteString("     *\n")
	for _, prop := range constructorProperties(properties) {
		fmt.Fprintf(&buf, "     * @param %s $%s\n", g.constructorParamDocType(prop), prop.Name)
	}
	buf.WriteString("     */\n")
	buf.WriteString("    public function __construct(")
	constructorProps := constructorProperties(properties)
	if len(constructorProps) > 0 {
		buf.WriteString("\n")
		for idx, prop := range constructorProps {
			buf.WriteString("        ")
			buf.WriteString(g.constructorParamType(prop))
			buf.WriteString(" $")
			buf.WriteString(prop.Name)
			if prop.Optional {
				buf.WriteString(" = null")
			}
			if idx < len(constructorProps)-1 {
				buf.WriteString(",")
			}
			buf.WriteString("\n")
		}
		buf.WriteString("    ")
	}
	buf.WriteString(") {\n")
	buf.WriteString("        \\SumUp\\Hydrator::hydrate([\n")
	for _, prop := range constructorProps {
		fmt.Fprintf(&buf, "            '%s' => $%s,\n", prop.SerializedName, prop.Name)
	}
	buf.WriteString("        ], self::class, $this);\n")
	buf.WriteString("    }\n\n")

	buf.WriteString("    /**\n")
	buf.WriteString("     * Create request DTO from an associative array.\n")
	buf.WriteString("     *\n")
	buf.WriteString("     * @param array<string, mixed> $data\n")
	buf.WriteString("     */\n")
	buf.WriteString("    public static function fromArray(array $data): self\n")
	buf.WriteString("    {\n")
	requiredProps := requiredProperties(properties)
	if len(requiredProps) > 0 {
		buf.WriteString("        self::assertRequiredFields($data, [\n")
		for _, prop := range requiredProps {
			fmt.Fprintf(&buf, "            '%s' => '%s',\n", prop.SerializedName, prop.Name)
		}
		buf.WriteString("        ]);\n\n")
	}
	buf.WriteString("        $request = (new \\ReflectionClass(self::class))->newInstanceWithoutConstructor();\n")
	buf.WriteString("        \\SumUp\\Hydrator::hydrate($data, self::class, $request);\n\n")
	buf.WriteString("        return $request;\n")
	buf.WriteString("    }\n\n")

	if len(requiredProps) > 0 {
		buf.WriteString("    /**\n")
		buf.WriteString("     * @param array<string, mixed> $data\n")
		buf.WriteString("     * @param array<string, string> $requiredFields\n")
		buf.WriteString("     */\n")
		buf.WriteString("    private static function assertRequiredFields(array $data, array $requiredFields): void\n")
		buf.WriteString("    {\n")
		buf.WriteString("        foreach ($requiredFields as $serializedName => $propertyName) {\n")
		buf.WriteString("            if (!array_key_exists($serializedName, $data) && !array_key_exists($propertyName, $data)) {\n")
		buf.WriteString("                throw new \\InvalidArgumentException(sprintf('Missing required field \"%s\".', $serializedName));\n")
		buf.WriteString("            }\n")
		buf.WriteString("        }\n")
		buf.WriteString("    }\n\n")
	}

	return buf.String()
}

func constructorProperties(properties []phpProperty) []phpProperty {
	result := make([]phpProperty, 0, len(properties))
	for _, prop := range properties {
		if !prop.Optional {
			result = append(result, prop)
		}
	}
	for _, prop := range properties {
		if prop.Optional {
			result = append(result, prop)
		}
	}
	return result
}

func requiredProperties(properties []phpProperty) []phpProperty {
	result := make([]phpProperty, 0, len(properties))
	for _, prop := range properties {
		if !prop.Optional {
			result = append(result, prop)
		}
	}
	return result
}

func (g *Generator) constructorParamDocType(prop phpProperty) string {
	docType := prop.DocType
	if docType == "" {
		docType = "mixed"
	}
	if backingType := g.enumBackingType(prop.Type); backingType != "" {
		docType += "|" + backingType
	}
	if (prop.Optional || prop.Nullable) && !strings.Contains(docType, "null") {
		docType += "|null"
	}
	return docType
}

func (g *Generator) constructorParamType(prop phpProperty) string {
	paramType := prop.Type
	if paramType == "" {
		paramType = "mixed"
	}

	if backingType := g.enumBackingType(paramType); backingType != "" {
		paramType += "|" + backingType
	}

	if (prop.Optional || prop.Nullable) && paramType != "mixed" {
		if strings.Contains(paramType, "|") {
			if !strings.Contains(paramType, "null") {
				paramType += "|null"
			}
		} else if !strings.HasPrefix(paramType, "?") {
			paramType = "?" + paramType
		}
	}

	return paramType
}

func (g *Generator) enumBackingType(typeName string) string {
	if typeName == "" {
		return ""
	}

	trimmed := strings.TrimPrefix(typeName, "\\")
	parts := strings.Split(trimmed, "\\")
	enumName := parts[len(parts)-1]
	if enumName == "" {
		return ""
	}

	for _, enums := range g.enumsByTag {
		for _, enum := range enums {
			if enum.Name == enumName {
				return enum.Type
			}
		}
	}

	return ""
}

func (g *Generator) collectRequestClassNames() {
	g.requestClassNames = make(map[string]struct{})
	for _, operations := range g.operationsByTag {
		for _, op := range operations {
			if op == nil || !op.HasBody || op.BodyType == "" {
				continue
			}
			className := phpClassBaseName(op.BodyType)
			if className == "" || isBuiltinPHPType(className) {
				continue
			}
			g.requestClassNames[className] = struct{}{}
		}
	}
}

func (g *Generator) shouldGenerateConstructorForClass(className string) bool {
	if className == "" || className == "BadRequest" {
		return false
	}
	if strings.HasSuffix(className, "Request") {
		return true
	}
	_, ok := g.requestClassNames[className]
	return ok
}

func phpClassBaseName(typeName string) string {
	trimmed := strings.TrimPrefix(typeName, "\\")
	if strings.Contains(trimmed, "|") {
		return ""
	}
	parts := strings.Split(trimmed, "\\")
	return parts[len(parts)-1]
}

func isBuiltinPHPType(typeName string) bool {
	switch typeName {
	case "array", "mixed", "string", "int", "float", "bool", "null", "void":
		return true
	default:
		return false
	}
}

func (g *Generator) displayTagName(tagKey string) string {
	if tagKey == sharedTagKey {
		return sharedTagDisplayName
	}
	if tagKey == typesTagKey {
		return typesTagDisplayName
	}

	if tag, ok := g.tagLookup[tagKey]; ok && tag != nil && tag.Name != "" {
		return sanitizeTagName(tag.Name)
	}

	return sanitizeTagName(tagKey)
}

func (g *Generator) tagDescription(tagKey string) string {
	if tag, ok := g.tagLookup[tagKey]; ok && tag != nil {
		return strings.TrimSpace(tag.Description)
	}
	return ""
}

func (g *Generator) namespaceForTag(tagKey string) string {
	if tagKey == sharedTagKey {
		return sharedTagNamespace
	}
	if tagKey == typesTagKey {
		return typesNamespace
	}

	tagName := g.displayTagName(tagKey)
	return fmt.Sprintf("SumUp\\%s", tagName)
}

func (g *Generator) buildPHPEnum(enum enumDefinition) string {
	var buf strings.Builder

	if enum.Description != "" {
		buf.WriteString("/**\n")
		for _, line := range strings.Split(enum.Description, "\n") {
			line = strings.TrimSpace(line)
			if line == "" {
				buf.WriteString(" *\n")
				continue
			}
			buf.WriteString(" * ")
			buf.WriteString(line)
			buf.WriteString("\n")
		}
		buf.WriteString(" */\n")
	}

	backingType := ""
	switch enum.Type {
	case "string":
		backingType = ": string"
	case "int":
		backingType = ": int"
	}

	fmt.Fprintf(&buf, "enum %s%s\n{\n", enum.Name, backingType)

	for _, value := range enum.Values {
		caseName := phpEnumCaseName(value)
		switch enum.Type {
		case "string":
			fmt.Fprintf(&buf, "    case %s = '%s';\n", caseName, value)
		case "int":
			fmt.Fprintf(&buf, "    case %s = %s;\n", caseName, value)
		default:
			fmt.Fprintf(&buf, "    case %s;\n", caseName)
		}
	}

	buf.WriteString("}\n")
	return buf.String()
}

func (g *Generator) shouldIncludeService(tagKey string, operations []*operation) bool {
	return tagKey != sharedTagKey && tagKey != typesTagKey && len(operations) > 0
}

func (g *Generator) collectEnums() (map[string][]enumDefinition, map[string]string) {
	enumsByTag := make(map[string][]enumDefinition)
	enumNamespaces := make(map[string]string)
	enumsSeen := make(map[string]struct{})

	tagKeys := slices.Collect(maps.Keys(g.schemasByTag))
	slices.Sort(tagKeys)

	for _, tagKey := range tagKeys {
		schemas := g.schemasByTag[tagKey]
		for _, schema := range schemas {
			g.collectEnumsFromSchema(schema, tagKey, enumsByTag, enumNamespaces, enumsSeen, make(map[*base.SchemaProxy]struct{}))
		}
	}

	// Sort enums by name within each tag
	for tag := range enumsByTag {
		slices.SortFunc(enumsByTag[tag], func(a, b enumDefinition) int {
			return strings.Compare(a.Name, b.Name)
		})
	}

	return enumsByTag, enumNamespaces
}

func (g *Generator) collectEnumsFromSchema(schema *base.SchemaProxy, tagKey string, enumsByTag map[string][]enumDefinition, enumNamespaces map[string]string, enumsSeen map[string]struct{}, visited map[*base.SchemaProxy]struct{}) {
	if schema == nil {
		return
	}

	if _, ok := visited[schema]; ok {
		return
	}
	visited[schema] = struct{}{}

	spec := schema.Schema()
	if spec == nil {
		return
	}

	// Check properties for enums
	if spec.Properties != nil {
		for propName, propSchema := range spec.Properties.FromOldest() {
			if propSchema == nil {
				continue
			}

			propSpec := propSchema.Schema()
			if propSpec == nil {
				continue
			}

			if len(propSpec.Enum) > 0 {
				schemaName := g.classNameForSchema(schema)
				if schemaName == "" {
					continue
				}
				enumName := phpEnumName(schemaName, propName)
				if _, seen := enumsSeen[enumName]; seen {
					continue
				}
				enumsSeen[enumName] = struct{}{}

				enumType := "string"
				values := make([]string, 0, len(propSpec.Enum))
				for _, val := range propSpec.Enum {
					if val != nil && val.Value != "" {
						values = append(values, val.Value)
					}
				}

				if len(values) > 0 {
					enum := enumDefinition{
						Name:        enumName,
						Description: propSpec.Description,
						Values:      values,
						Type:        enumType,
					}
					enumsByTag[tagKey] = append(enumsByTag[tagKey], enum)
					enumNamespaces[enumName] = g.namespaceForTag(tagKey)
				}
			}

			// Recursively check nested schemas
			g.collectEnumsFromSchema(propSchema, tagKey, enumsByTag, enumNamespaces, enumsSeen, visited)
		}
	}

	// Check allOf, anyOf, oneOf compositions
	for _, composite := range spec.AllOf {
		g.collectEnumsFromSchema(composite, tagKey, enumsByTag, enumNamespaces, enumsSeen, visited)
	}
	for _, composite := range spec.AnyOf {
		g.collectEnumsFromSchema(composite, tagKey, enumsByTag, enumNamespaces, enumsSeen, visited)
	}
	for _, composite := range spec.OneOf {
		g.collectEnumsFromSchema(composite, tagKey, enumsByTag, enumNamespaces, enumsSeen, visited)
	}
}
