// Native setHTML() when available. Fallback for browsers without it
// (currently: Safari/iOS) implements the same allow-list model as the
// native default Sanitizer: nothing survives unless it's explicitly
// listed here. This is deliberately a SUBSET of the real default list -
// the common text/structural elements EME's rich-text and balloon
// content actually uses - not a full spec replica.
(function () {
    const DEFAULT_ALLOWED_ELEMENTS = [
        'a', 'abbr', 'address', 'article', 'aside', 'b', 'bdi', 'bdo',
        'blockquote', 'br', 'caption', 'cite', 'code', 'data', 'dd', 'del',
        'dfn', 'div', 'dl', 'dt', 'em', 'figcaption', 'figure', 'footer',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hgroup', 'hr', 'i',
        'ins', 'kbd', 'li', 'main', 'mark', 'nav', 'ol', 'p', 'pre', 'q',
        'rp', 'rt', 'ruby', 's', 'samp', 'section', 'small', 'span',
        'strong', 'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'tfoot',
        'th', 'thead', 'time', 'tr', 'u', 'ul', 'var', 'wbr',
        'col', 'colgroup'
    ];

    const DEFAULT_ELEMENT_ATTRS = {
        a: ['href', 'hreflang', 'type'],
        blockquote: ['cite'],
        col: ['span'],
        colgroup: ['span'],
        data: ['value'],
        del: ['cite', 'datetime'],
        ins: ['cite', 'datetime'],
        ol: ['reversed', 'start', 'type'],
        td: ['colspan', 'headers', 'rowspan'],
        th: ['abbr', 'colspan', 'headers', 'rowspan', 'scope'],
        time: ['datetime']
    };

    const GLOBAL_ALLOWED_ATTRS = ['dir', 'lang', 'title'];

    // URL-bearing attributes get an extra scheme check even when allowed
    const URL_ATTRS = ['href', 'src', 'action', 'formaction', 'xlink:href'];
    const UNSAFE_SCHEMES = ['javascript:', 'vbscript:', 'data:text/html'];

    function defaultElementMap() {
        const map = new Map();
        DEFAULT_ALLOWED_ELEMENTS.forEach(name => {
            map.set(name, new Set([...GLOBAL_ALLOWED_ATTRS, ...(DEFAULT_ELEMENT_ATTRS[name] || [])]));
        });
        return map;
    }

    // Minimal Sanitizer polyfill: only the constructor + allowElement()
    // that EME actually calls. Not a full spec implementation - extend
    // if a future call site needs removeElement/allowAttribute/comments/etc.
    if (!('Sanitizer' in window)) {
        window.Sanitizer = function (config) {
            this._elements = (!config || Object.keys(config).length !== 0)
                ? defaultElementMap()
                : new Map();
        };

        window.Sanitizer.prototype.allowElement = function (el) {
            const name = typeof el === 'string' ? el : el.name;
            const attrs = typeof el === 'string' ? [] : (el.attributes || []);
            // Like the native allowElement(), this REPLACES the element's
            // attribute set (it does not merge). Re-list any attr you keep.
            this._elements.set(name, new Set([...GLOBAL_ALLOWED_ATTRS, ...attrs]));
            return this;
        };
    }

    if (!('setHTML' in Element.prototype)) {
        Element.prototype.setHTML = function (input, options) {
            const sanitizer = options && options.sanitizer;
            const allowedElements = (sanitizer && sanitizer._elements) || defaultElementMap();

            const template = document.createElement('template');
            template.innerHTML = input;
            const fragment = template.content;

            // Strip comments outright, same as the native default
            const commentWalker = document.createNodeIterator(fragment, NodeFilter.SHOW_COMMENT);
            let c;
            while ((c = commentWalker.nextNode())) {
                c.remove();
            }

            const iterator = document.createNodeIterator(fragment, NodeFilter.SHOW_ELEMENT);
            let currentNode;
            while ((currentNode = iterator.nextNode())) {
                const tagName = currentNode.tagName.toLowerCase();

                if (!allowedElements.has(tagName)) {
                    currentNode.remove(); // whole subtree dropped, matching native default behavior
                    continue;
                }

                const allowedAttrs = allowedElements.get(tagName);
                const attrs = currentNode.attributes;
                for (let i = attrs.length - 1; i >= 0; i--) {
                    const attr = attrs[i];
                    const attrName = attr.name.toLowerCase();

                    if (!allowedAttrs.has(attrName)) {
                        currentNode.removeAttribute(attr.name); // data-*, on*, anything unlisted — all unlisted by default
                        continue;
                    }

                    if (URL_ATTRS.includes(attrName)) {
                        const value = attr.value.trim().toLowerCase().replace(/[\t\n\r]/g, '');
                        if (UNSAFE_SCHEMES.some(scheme => value.startsWith(scheme))) {
                            currentNode.removeAttribute(attr.name);
                        }
                    }
                }
            }

            this.replaceChildren(fragment);
        };
    }
})();
