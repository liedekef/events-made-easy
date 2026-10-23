package generator

import (
	"strings"

	"github.com/iancoleman/strcase"
	"github.com/pb33f/libopenapi/datamodel/high/base"
)

func schemaClassName(schema *base.SchemaProxy) string {
	if schema == nil {
		return ""
	}

	if ref := schema.GetReference(); ref != "" {
		name := strings.TrimPrefix(ref, "#/components/schemas/")
		name = strings.ReplaceAll(name, ".", "_")
		name = strings.ReplaceAll(name, "-", "_")
		return strcase.ToCamel(name)
	}

	if schema.Schema() != nil && schema.Schema().Title != "" {
		return strcase.ToCamel(schema.Schema().Title)
	}

	return "Model"
}

func normalizeTagKey(tag string) string {
	tag = strings.TrimSpace(strings.ToLower(tag))
	if tag == "" {
		return "default"
	}
	return tag
}

func sanitizeTagName(tag string) string {
	tag = strings.TrimSpace(tag)
	tag = strings.ReplaceAll(tag, "-", " ")
	tag = strings.ReplaceAll(tag, "_", " ")
	if tag == "" {
		return "Default"
	}
	return strcase.ToCamel(tag)
}

func phpEnumName(schemaName, propertyName string) string {
	propertyName = strings.TrimSpace(propertyName)
	propertyName = strings.ReplaceAll(propertyName, "-", "_")
	propertyName = strings.ReplaceAll(propertyName, ".", "_")

	baseName := strcase.ToCamel(propertyName)
	return schemaName + baseName
}

func phpInlineObjectName(schemaName, propertyName string) string {
	propertyName = strings.TrimSpace(propertyName)
	propertyName = strings.ReplaceAll(propertyName, "-", "_")
	propertyName = strings.ReplaceAll(propertyName, ".", "_")

	baseName := strcase.ToCamel(propertyName)
	return schemaName + baseName
}

func phpEnumCaseName(value string) string {
	value = strings.TrimSpace(value)

	// Handle numeric values or values that start with numbers
	if len(value) > 0 && value[0] >= '0' && value[0] <= '9' {
		value = "VALUE_" + value
	}

	// Replace common separators and special characters
	value = strings.ReplaceAll(value, "+", "_PLUS_")
	value = strings.ReplaceAll(value, "-", "_")
	value = strings.ReplaceAll(value, ".", "_")
	value = strings.ReplaceAll(value, " ", "_")
	value = strings.ReplaceAll(value, "/", "_")
	value = strings.ReplaceAll(value, "(", "_")
	value = strings.ReplaceAll(value, ")", "_")
	value = strings.ReplaceAll(value, "&", "_AND_")
	value = strings.ReplaceAll(value, "%", "_PERCENT_")
	value = strings.ReplaceAll(value, "#", "_HASH_")
	value = strings.ReplaceAll(value, "@", "_AT_")
	value = strings.ReplaceAll(value, "!", "_")
	value = strings.ReplaceAll(value, "?", "_")
	value = strings.ReplaceAll(value, ":", "_")
	value = strings.ReplaceAll(value, ";", "_")
	value = strings.ReplaceAll(value, ",", "_")
	value = strings.ReplaceAll(value, "'", "_")
	value = strings.ReplaceAll(value, "\"", "_")

	// Convert to screaming snake case
	value = strcase.ToScreamingSnake(value)

	// Clean up multiple underscores
	for strings.Contains(value, "__") {
		value = strings.ReplaceAll(value, "__", "_")
	}

	// Ensure it doesn't start with underscore (unless it's a special value)
	value = strings.TrimLeft(value, "_")

	if value == "" {
		return "EMPTY"
	}

	return value
}
