// Minimal Element.prototype.setHTML polyfill.
// Uses the native setHTML (default sanitizer) when available.
// For browsers without it (as of writing: all of Safari/iOS, plus older
// Chrome/Firefox), falls back to a DOM-based sanitizer that removes
// XSS-unsafe elements and attributes (scripts, event handlers, javascript:
// URLs, <base> hijacking, etc). Since Safari has no native support yet,
// this fallback is the primary path for a large share of visitors, not
// just a rare edge case - keep it in sync with the native default
// Sanitizer's coverage rather than treating it as a stopgap.
if ( ! ( 'setHTML' in Element.prototype ) ) {
    const UNSAFE_TAGS = ['SCRIPT', 'IFRAME', 'OBJECT', 'EMBED', 'FRAME', 'APPLET', 'LINK', 'META', 'STYLE', 'BASE'];
    const UNSAFE_ATTRS = ['srcdoc'];
    const UNSAFE_SCHEMES = ['javascript:', 'vbscript:', 'data:text/html'];
    const URL_ATTRS = ['href', 'src', 'action', 'formaction', 'xlink:href'];

    Element.prototype.setHTML = function( input ) {
        const template = document.createElement( 'template' );
        template.innerHTML = input;
        const fragment = template.content;

        const iterator = document.createNodeIterator( fragment, NodeFilter.SHOW_ELEMENT );
        let currentNode;
        while ( ( currentNode = iterator.nextNode() ) ) {
            const tagName = currentNode.tagName;
            if ( UNSAFE_TAGS.includes( tagName ) ) {
                currentNode.remove();
                continue;
            }

            const attrs = currentNode.attributes;
            if ( attrs ) {
                for ( let i = attrs.length - 1; i >= 0; i-- ) {
                    const attr = attrs[i];
                    const attrName = attr.name.toLowerCase();
                    // Browsers ignore ASCII tabs/newlines anywhere in a URL
                    // before resolving its scheme, so strip them here too -
                    // otherwise "jav\tascript:" slips past a plain startsWith check.
                    const attrValue = attr.value.trim().toLowerCase().replace( /[\t\n\r]/g, '' );

                    const isEventHandler = attrName.startsWith( 'on' ) && attrName.length > 2;
                    const isUnsafeAttr = UNSAFE_ATTRS.includes( attrName );
                    const isUnsafeUrl = URL_ATTRS.includes( attrName ) &&
                        UNSAFE_SCHEMES.some( scheme => attrValue.startsWith( scheme ) );

                    if ( isEventHandler || isUnsafeAttr || isUnsafeUrl ) {
                        currentNode.removeAttribute( attr.name );
                    }
                }
            }
        }

        this.replaceChildren( fragment );
    };
}
