package generator

import (
	"log/slog"
	"slices"
	"strings"

	"github.com/pb33f/libopenapi/datamodel/high/base"
	v3 "github.com/pb33f/libopenapi/datamodel/high/v3"
)

type schemaUsage struct {
	name   string
	schema *base.SchemaProxy
	tags   map[string]struct{}
}

func (g *Generator) collectSchemaUsage() map[string]*schemaUsage {
	usage := make(map[string]*schemaUsage)

	if g.spec == nil || g.spec.Paths == nil {
		return usage
	}

	for path, pathItem := range g.spec.Paths.PathItems.FromOldest() {
		for method, op := range pathItem.GetOperations().FromOldest() {
			opTags := make([]string, 0, len(op.Tags))
			for _, tag := range op.Tags {
				opTags = append(opTags, normalizeTagKey(tag))
			}

			g.collectSchemaUsageInResponse(op, opTags, usage)
			g.collectSchemaUsageInParams(op, opTags, usage)
			g.collectSchemaUsageInRequest(op, opTags, usage)

			if len(op.Tags) == 0 {
				slog.Warn("operation without tags; skipping schema assignment",
					slog.String("path", path),
					slog.String("method", method),
				)
			}
		}
	}

	return usage
}

func (g *Generator) collectSchemaUsageInResponse(op *v3.Operation, tags []string, usage map[string]*schemaUsage) {
	if op == nil || op.Responses == nil || op.Responses.Codes.Len() == 0 {
		return
	}

	for _, response := range op.Responses.Codes.FromOldest() {
		if response.Content == nil {
			continue
		}

		for _, mediaType := range response.Content.FromOldest() {
			g.collectSchemaUsageFromSchema(mediaType.Schema, tags, usage, make(map[*base.SchemaProxy]struct{}), "")
		}
	}
}

func (g *Generator) collectSchemaUsageInParams(op *v3.Operation, tags []string, usage map[string]*schemaUsage) {
	if op == nil {
		return
	}

	for _, param := range op.Parameters {
		g.collectSchemaUsageFromSchema(param.Schema, tags, usage, make(map[*base.SchemaProxy]struct{}), "")
	}
}

func (g *Generator) collectSchemaUsageInRequest(op *v3.Operation, tags []string, usage map[string]*schemaUsage) {
	if op == nil || op.RequestBody == nil {
		return
	}

	for _, mediaType := range op.RequestBody.Content.FromOldest() {
		g.collectSchemaUsageFromSchema(mediaType.Schema, tags, usage, make(map[*base.SchemaProxy]struct{}), "")
	}
}

func (g *Generator) collectSchemaUsageFromSchema(schema *base.SchemaProxy, tags []string, usage map[string]*schemaUsage, stack map[*base.SchemaProxy]struct{}, suggestedName string) {
	if schema == nil {
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

	name := g.registerSchemaUsage(schema, suggestedName, tags, usage)
	parentName := name
	if parentName == "" {
		parentName = suggestedName
	}

	if spec.Properties != nil {
		for propName, propSchema := range spec.Properties.FromOldest() {
			childName := g.inlinePropertyClassName(parentName, propName, propSchema)
			g.collectSchemaUsageFromSchema(propSchema, tags, usage, stack, childName)
		}
	}

	if hasSchemaType(spec, "array") && spec.Items != nil && spec.Items.A != nil {
		itemName := g.inlineArrayItemClassName(parentName, spec.Items.A)
		g.collectSchemaUsageFromSchema(spec.Items.A, tags, usage, stack, itemName)
	}

	for _, composite := range spec.AllOf {
		g.collectSchemaUsageFromSchema(composite, tags, usage, stack, parentName)
	}
	for _, composite := range spec.AnyOf {
		g.collectSchemaUsageFromSchema(composite, tags, usage, stack, parentName)
	}
	for _, composite := range spec.OneOf {
		g.collectSchemaUsageFromSchema(composite, tags, usage, stack, parentName)
	}
}

func (g *Generator) registerSchemaUsage(schema *base.SchemaProxy, suggestedName string, tags []string, usage map[string]*schemaUsage) string {
	if schema == nil {
		return ""
	}

	name := g.classNameForSchema(schema)
	if name == "" {
		if suggestedName == "" || !schemaShouldGenerateClass(schema) {
			return ""
		}
		name = suggestedName
		g.inlineSchemaNames[schema] = name
	}

	entry, ok := usage[name]
	if !ok {
		entry = &schemaUsage{
			name:   name,
			schema: schema,
			tags:   make(map[string]struct{}),
		}
		usage[name] = entry
	}

	for _, tag := range tags {
		entry.tags[tag] = struct{}{}
	}

	return name
}

func (g *Generator) assignSchemasToTags(usage map[string]*schemaUsage) (map[string][]*base.SchemaProxy, map[string]string) {
	result := make(map[string][]*base.SchemaProxy)
	namespaceBySchema := make(map[string]string)

	for schemaName, info := range usage {
		targetTag := typesTagKey

		// Skip schemas that should remain generic maps in PHP.
		if !schemaShouldGenerateClass(info.schema) {
			continue
		}

		if schemaIsObject(info.schema) {
			result[targetTag] = append(result[targetTag], info.schema)
			namespaceBySchema[schemaName] = g.namespaceForTag(targetTag)
		}
	}

	for tag := range result {
		slices.SortFunc(result[tag], func(a, b *base.SchemaProxy) int {
			return strings.Compare(g.classNameForSchema(a), g.classNameForSchema(b))
		})
	}

	return result, namespaceBySchema
}

func (g *Generator) classNameForSchema(schema *base.SchemaProxy) string {
	if schema == nil {
		return ""
	}

	if ref := schema.GetReference(); ref != "" {
		return schemaClassName(schema)
	}

	if name, ok := g.inlineSchemaNames[schema]; ok {
		return name
	}

	return ""
}

func (g *Generator) inlinePropertyClassName(parentName string, propertyName string, schema *base.SchemaProxy) string {
	if parentName == "" || schema == nil {
		return ""
	}

	if schema.GetReference() != "" {
		return ""
	}

	if !schemaShouldGenerateClass(schema) {
		return ""
	}

	return phpInlineObjectName(parentName, propertyName)
}

func (g *Generator) inlineArrayItemClassName(parentName string, schema *base.SchemaProxy) string {
	if parentName == "" || schema == nil {
		return ""
	}

	if schema.GetReference() != "" {
		return ""
	}

	if !schemaShouldGenerateClass(schema) {
		return ""
	}

	return parentName + "Item"
}
