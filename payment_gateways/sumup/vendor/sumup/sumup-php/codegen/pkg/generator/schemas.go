package generator

import "github.com/pb33f/libopenapi/datamodel/high/base"

// schemaIsObject reports whether the provided schema describes an object that
// warrants generating a PHP class. It walks nested compositions (allOf/oneOf/anyOf)
// to account for aggregated object schemas.
func schemaIsObject(schema *base.SchemaProxy) bool {
	return schemaIsObjectWithStack(schema, make(map[*base.SchemaProxy]struct{}))
}

// schemaIsAdditionalPropertiesOnly checks if a schema is an object with only
// additionalProperties defined (no explicit properties). These should extend ArrayObject.
func schemaIsAdditionalPropertiesOnly(schema *base.SchemaProxy) bool {
	if schema == nil {
		return false
	}

	spec := schema.Schema()
	if spec == nil {
		return false
	}

	// Must be an object type
	if !hasSchemaType(spec, "object") {
		return false
	}

	// Must have no explicit properties
	if spec.Properties != nil && spec.Properties.Len() > 0 {
		return false
	}

	// Must have additionalProperties defined
	if spec.AdditionalProperties == nil {
		return false
	}

	return true
}

// schemaHasDeclaredProperties reports whether the schema exposes at least one
// explicit property directly or through allOf/oneOf/anyOf composition.
func schemaHasDeclaredProperties(schema *base.SchemaProxy) bool {
	return schemaHasDeclaredPropertiesWithStack(schema, make(map[*base.SchemaProxy]struct{}))
}

// schemaShouldGenerateClass reports whether the schema should become a DTO
// class instead of a generic map. Bare object schemas and
// additionalProperties-only schemas stay as arrays in the PHP SDK.
func schemaShouldGenerateClass(schema *base.SchemaProxy) bool {
	return schemaIsObject(schema) && schemaHasDeclaredProperties(schema) && !schemaIsAdditionalPropertiesOnly(schema)
}

func schemaHasDeclaredPropertiesWithStack(schema *base.SchemaProxy, stack map[*base.SchemaProxy]struct{}) bool {
	if schema == nil {
		return false
	}

	if _, ok := stack[schema]; ok {
		return false
	}
	stack[schema] = struct{}{}
	defer delete(stack, schema)

	spec := schema.Schema()
	if spec == nil {
		return false
	}

	if spec.Properties != nil && spec.Properties.Len() > 0 {
		return true
	}

	for _, composite := range spec.AllOf {
		if schemaHasDeclaredPropertiesWithStack(composite, stack) {
			return true
		}
	}

	for _, composite := range spec.AnyOf {
		if schemaHasDeclaredPropertiesWithStack(composite, stack) {
			return true
		}
	}

	for _, composite := range spec.OneOf {
		if schemaHasDeclaredPropertiesWithStack(composite, stack) {
			return true
		}
	}

	return false
}

func schemaIsObjectWithStack(schema *base.SchemaProxy, stack map[*base.SchemaProxy]struct{}) bool {
	if schema == nil {
		return false
	}

	if _, ok := stack[schema]; ok {
		return false
	}
	stack[schema] = struct{}{}
	defer delete(stack, schema)

	spec := schema.Schema()
	if spec == nil {
		return false
	}

	if hasSchemaType(spec, "object") {
		return true
	}

	if spec.Properties != nil && spec.Properties.Len() > 0 {
		return true
	}

	for _, composite := range spec.AllOf {
		if schemaIsObjectWithStack(composite, stack) {
			return true
		}
	}

	for _, composite := range spec.AnyOf {
		if schemaIsObjectWithStack(composite, stack) {
			return true
		}
	}

	for _, composite := range spec.OneOf {
		if schemaIsObjectWithStack(composite, stack) {
			return true
		}
	}

	return false
}
