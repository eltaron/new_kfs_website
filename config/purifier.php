<?php

/**
 * HTMLPurifier profiles used with clean() / Purifier::clean().
 *
 *  - default : staff/user-entered rich text (admin replies, notes, GIS
 *              requirements). Basic formatting, lists, tables, links, images.
 *  - cms     : CMS article/project/investment bodies. Same as default plus
 *              iframes restricted to YouTube and Google Maps embeds.
 *  - text    : strips everything except line breaks and basic emphasis.
 *
 * Usage in Blade: {!! clean($html) !!}  or  {!! clean($html, 'cms') !!}
 */
return [
    'encoding' => 'UTF-8',
    'finalize' => true,
    'ignoreNonStrings' => false,
    'cachePath' => storage_path('app/purifier'),
    'cacheFileMode' => 0755,

    'settings' => [
        'default' => [
            'HTML.Doctype' => 'HTML 4.01 Transitional',
            'HTML.Allowed' => implode(',', [
                'div[style|dir|class]', 'p[style|dir|class]', 'span[style|dir|class]', 'br', 'hr',
                'b', 'strong', 'i', 'em', 'u', 's', 'sub', 'sup', 'mark', 'small', 'blockquote', 'pre', 'code',
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                'ul', 'ol[start]', 'li',
                'table[border|cellpadding|cellspacing|width|style]', 'thead', 'tbody', 'tfoot',
                'tr', 'th[colspan|rowspan|style|scope]', 'td[colspan|rowspan|style]', 'caption',
                'a[href|title|target|rel]',
                'img[src|alt|title|width|height|style]',
                'figure', 'figcaption',
            ]),
            'CSS.AllowedProperties' => 'font,font-size,font-weight,font-style,font-family,text-decoration,'
                . 'color,background-color,text-align,direction,padding,padding-left,padding-right,'
                . 'margin,margin-left,margin-right,width,height,border,border-collapse,list-style-type,line-height',
            'URI.AllowedSchemes' => ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true],
            'HTML.TargetBlank' => true,
            'HTML.Nofollow' => true,
            'Attr.AllowedFrameTargets' => ['_blank'],
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => true,
        ],

        'cms' => [
            'HTML.Doctype' => 'HTML 4.01 Transitional',
            'HTML.Allowed' => implode(',', [
                'div[style|dir|class]', 'p[style|dir|class]', 'span[style|dir|class]', 'br', 'hr',
                'b', 'strong', 'i', 'em', 'u', 's', 'sub', 'sup', 'mark', 'small', 'blockquote', 'pre', 'code',
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                'ul', 'ol[start]', 'li',
                'table[border|cellpadding|cellspacing|width|style]', 'thead', 'tbody', 'tfoot',
                'tr', 'th[colspan|rowspan|style|scope]', 'td[colspan|rowspan|style]', 'caption',
                'a[href|title|target|rel]',
                'img[src|alt|title|width|height|style]',
                'figure', 'figcaption',
                'iframe[src|width|height|frameborder|allow|allowfullscreen|title|style]',
            ]),
            'CSS.AllowedProperties' => 'font,font-size,font-weight,font-style,font-family,text-decoration,'
                . 'color,background-color,text-align,direction,padding,padding-left,padding-right,'
                . 'margin,margin-left,margin-right,width,height,max-width,border,border-collapse,list-style-type,line-height',
            'URI.AllowedSchemes' => ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true],
            'HTML.TargetBlank' => true,
            'Attr.AllowedFrameTargets' => ['_blank'],
            // iframes: YouTube and Google Maps embeds only.
            'HTML.SafeIframe' => true,
            'URI.SafeIframeRegexp' => '%^(https?:)?//(www\.youtube\.com/embed/|www\.youtube-nocookie\.com/embed/|www\.google\.com/maps/embed|maps\.google\.com/maps)%',
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => true,
        ],

        'text' => [
            'HTML.Allowed' => 'br,b,strong,i,em,u,p',
            'AutoFormat.AutoParagraph' => false,
        ],

        'custom_definition' => [
            'id' => 'kfs-html5-definitions',
            'rev' => 2,
            'debug' => false,
            'elements' => [
                ['section', 'Block', 'Flow', 'Common'],
                ['article', 'Block', 'Flow', 'Common'],
                ['figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common'],
                ['figcaption', 'Inline', 'Flow', 'Common'],
                ['s', 'Inline', 'Inline', 'Common'],
                ['sub', 'Inline', 'Inline', 'Common'],
                ['sup', 'Inline', 'Inline', 'Common'],
                ['mark', 'Inline', 'Inline', 'Common'],
            ],
            'attributes' => [
                ['iframe', 'allowfullscreen', 'Bool'],
                ['iframe', 'allow', 'Text'],
                ['table', 'height', 'Text'],
                ['td', 'border', 'Text'],
                ['th', 'border', 'Text'],
                ['th', 'scope', 'Text'],
                ['tr', 'width', 'Text'],
                ['tr', 'height', 'Text'],
                ['tr', 'border', 'Text'],
            ],
        ],
        'custom_attributes' => [
            ['a', 'target', 'Enum#_blank,_self'],
            ['a', 'rel', 'Text'],
            ['div', 'dir', 'Enum#rtl,ltr,auto'],
            ['p', 'dir', 'Enum#rtl,ltr,auto'],
            ['span', 'dir', 'Enum#rtl,ltr,auto'],
        ],
        'custom_elements' => [
            ['u', 'Inline', 'Inline', 'Common'],
        ],
    ],
];
