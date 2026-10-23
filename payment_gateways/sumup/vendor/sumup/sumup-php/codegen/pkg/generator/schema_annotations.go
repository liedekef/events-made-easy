package generator

import (
	"iter"
	"slices"
	"strings"

	"github.com/pb33f/libopenapi/datamodel/high/base"
	v3 "github.com/pb33f/libopenapi/datamodel/high/v3"
)

// normalizeSchemaAnnotations gives the generator a resolved view of annotated
// references and single-schema allOf wrappers without changing reference names.
// Only annotation-only branches are removed; validation constraints stay intact.
func normalizeSchemaAnnotations(doc *v3.Document) {
	if doc == nil {
		return
	}
	seen := make(map[*base.SchemaProxy]bool)
	var visit func(*base.SchemaProxy)
	visit = func(proxy *base.SchemaProxy) {
		if proxy == nil || seen[proxy] {
			return
		}
		seen[proxy] = true
		originalRef := proxy.GetReference()
		schema := proxy.Schema()
		if schema == nil {
			return
		}
		if proxy.IsTransformedRefWithSiblings() && annotationOnlySchema(schema, false) && doc.Components != nil && doc.Components.Schemas != nil {
			ref := proxy.GetReference()
			name, ok := strings.CutPrefix(ref, "#/components/schemas/")
			if ok {
				target := doc.Components.Schemas.GetOrZero(name)
				visit(target)
				if target != nil && target.Schema() != nil {
					resolved := *target.Schema()
					copySchemaAnnotations(&resolved, schema)
					*proxy = *base.CreateSchemaProxyRefWithSchema(ref, &resolved)
					schema = proxy.Schema()
				}
			}
		}
		for _, child := range schema.Properties.FromOldest() {
			visit(child)
		}
		if schema.Items != nil && schema.Items.IsA() {
			visit(schema.Items.A)
		}
		if schema.AdditionalProperties != nil && schema.AdditionalProperties.IsA() {
			visit(schema.AdditionalProperties.A)
		}
		for _, child := range schema.AllOf {
			visit(child)
		}
		for _, child := range schema.AnyOf {
			visit(child)
		}
		for _, child := range schema.OneOf {
			visit(child)
		}
		if len(schema.AllOf) == 0 || !annotationOnlySchema(schema, true) {
			return
		}
		var target *base.SchemaProxy
		for _, child := range schema.AllOf {
			if child == nil || child.Schema() == nil {
				return
			}
			if annotationOnlySchema(child.Schema(), false) && !child.IsReference() {
				continue
			}
			if target != nil {
				return
			}
			target = child
		}
		if target == nil {
			return
		}
		// Keep object composition and its dependency edges. Existing generators
		// already flatten objects, and those edges determine public exports.
		shape := target.Schema()
		if slices.Contains(shape.Type, "object") ||
			(shape.Properties != nil && shape.Properties.Len() > 0) ||
			shape.AdditionalProperties != nil || len(shape.AllOf) > 0 {
			return
		}
		resolved := *shape
		for _, child := range schema.AllOf {
			if child != target {
				copySchemaAnnotations(&resolved, child.Schema())
			}
		}
		copySchemaAnnotations(&resolved, schema)
		if originalRef != "" {
			*proxy = *base.CreateSchemaProxyRefWithSchema(originalRef, &resolved)
		} else if target.IsReference() {
			*proxy = *base.CreateSchemaProxyRefWithSchema(target.GetReference(), &resolved)
		} else {
			*proxy = *base.CreateSchemaProxy(&resolved)
		}
	}
	if doc.Components != nil && doc.Components.Schemas != nil {
		for _, schema := range doc.Components.Schemas.FromOldest() {
			visit(schema)
		}
	}
	visitContent := func(content iter.Seq2[string, *v3.MediaType]) {
		for _, media := range content {
			if media != nil {
				visit(media.Schema)
			}
		}
	}
	visitParameter := func(parameter *v3.Parameter) {
		if parameter == nil {
			return
		}
		visit(parameter.Schema)
		visitContent(parameter.Content.FromOldest())
	}
	visitResponse := func(response *v3.Response) {
		if response != nil {
			visitContent(response.Content.FromOldest())
		}
	}
	visitPath := func(item *v3.PathItem) {
		if item == nil {
			return
		}
		for _, parameter := range item.Parameters {
			visitParameter(parameter)
		}
		for _, operation := range item.GetOperations().FromOldest() {
			for _, parameter := range operation.Parameters {
				visitParameter(parameter)
			}
			if operation.RequestBody != nil {
				visitContent(operation.RequestBody.Content.FromOldest())
			}
			if operation.Responses != nil {
				for _, response := range operation.Responses.Codes.FromOldest() {
					visitResponse(response)
				}
				visitResponse(operation.Responses.Default)
			}
		}
	}
	if doc.Paths != nil {
		for _, item := range doc.Paths.PathItems.FromOldest() {
			visitPath(item)
		}
	}
	for _, item := range doc.Webhooks.FromOldest() {
		visitPath(item)
	}
}

func annotationOnlySchema(schema *base.Schema, allowAllOf bool) bool {
	if schema == nil || schema.GoLow() == nil || schema.GoLow().RootNode == nil {
		return false
	}
	node := schema.GoLow().RootNode
	for i := 0; i+1 < len(node.Content); i += 2 {
		switch node.Content[i].Value {
		case "title", "description", "$comment", "default", "example", "examples", "deprecated", "readOnly", "writeOnly", "externalDocs":
		case "allOf":
			if !allowAllOf {
				return false
			}
		default:
			return false
		}
	}
	return true
}

func copySchemaAnnotations(target, source *base.Schema) {
	if source.Title != "" {
		target.Title = source.Title
	}
	if source.Description != "" {
		target.Description = source.Description
	}
	if source.Default != nil {
		target.Default = source.Default
	}
	if source.Example != nil {
		target.Example = source.Example
	}
	if source.Examples != nil {
		target.Examples = source.Examples
	}
	if source.Deprecated != nil {
		target.Deprecated = source.Deprecated
	}
	if source.ReadOnly != nil {
		target.ReadOnly = source.ReadOnly
	}
	if source.WriteOnly != nil {
		target.WriteOnly = source.WriteOnly
	}
	if source.ExternalDocs != nil {
		target.ExternalDocs = source.ExternalDocs
	}
}
