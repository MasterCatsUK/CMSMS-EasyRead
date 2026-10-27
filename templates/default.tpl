{if !empty($easyread_blocks)}
          <div class="easyread-summary py-4">
            <header class="mb-5">
              <h2>Easy Read</h2>
              <p class="lead">This page has pictures and easy-to-read information.</p>
            </header>
            {foreach $easyread_blocks as $easyItem}
              {assign var='easyImage' value=$easyItem.image_url|default:''|trim}
              {assign var='easyText' value=$easyItem.content|default:''|strip_tags|replace:'&nbsp;':''|replace:'&#160;':''|replace:'&#xA0;':''|replace:' ':''|trim}
              {if $easyImage != '' || $easyText != ''}
              <div class="easyread-summary-item row align-items-center g-4 mb-5">
                {if $easyImage != ''}
                <div class="col-12 col-md-4">
                  <div class="easy-read-image">
                    <img src="{$easyImage|escape:'html'}" alt="{$easyItem.alt|default:''|escape:'html'}" class="img-fluid rounded easyread-summary-image" loading="lazy">
                  </div>
                </div>
                {/if}
                {if $easyText != ''}
                <div class="{if $easyImage != ''}col-12 col-md-8{else}col-12{/if}">
                  <div class="easy-read-content easyread-summary-text fs-4">
                    {$easyItem.content}
                  </div>
                </div>
                {/if}
              </div>
              {/if}
            {/foreach}
          </div>
{/if}
