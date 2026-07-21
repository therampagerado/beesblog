{** Warehouse responsive format candidates with one legacy fallback. **}
<picture class="beesblog-picture">
    {if isset($responsiveImage.srcset) && $responsiveImage.srcset}
        <source type="{$responsiveImage.modern_type|escape:'htmlall':'UTF-8'}"
                srcset="{$responsiveImage.srcset|escape:'htmlall':'UTF-8'}"
                sizes="{if isset($responsiveSizes) && $responsiveSizes}{$responsiveSizes|escape:'htmlall':'UTF-8'}{else}{$responsiveImage.sizes|escape:'htmlall':'UTF-8'}{/if}">
    {/if}
    <img class="img-responsive beesblog-responsive-image"
         src="{$responsiveImage.fallback_url|escape:'htmlall':'UTF-8'}"
         alt="{$responsiveAlt|escape:'htmlall':'UTF-8'}"
         width="{$responsiveImage.width|intval}"
         height="{$responsiveImage.height|intval}"
         loading="{$responsiveImage.loading|default:'lazy'|escape:'htmlall':'UTF-8'}"
         decoding="async"
         fetchpriority="{$responsiveImage.fetchpriority|default:'auto'|escape:'htmlall':'UTF-8'}"
         itemprop="image">
</picture>
