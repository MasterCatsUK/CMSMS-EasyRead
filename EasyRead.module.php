<?php
if (!defined('CMS_VERSION')) exit;

/**
 * Easy Read
 *
 * A deliberately small CMS Made Simple content-block module for repeatable
 * Easy Read rows: image upload, alt text and WYSIWYG content.
 */
class EasyRead extends CMSModule
{
    const VERSION = '0.1.23';
    const UPLOAD_SUBDIR = 'EasyRead';
    const MAX_UPLOAD_BYTES = 8388608; // 8 MiB
    const VIEW_EXTENSION_PERMISSION = 'View Easy Read in Extensions';

    /** @var array<string,string> */
    private $contentBlockErrors = array();

    public function GetName()
    {
        return 'EasyRead';
    }

    public function GetFriendlyName()
    {
        return 'Easy Read';
    }

    public function GetVersion()
    {
        return self::VERSION;
    }

    public function MinimumCMSVersion()
    {
        return '2.2.14';
    }

    public function GetAuthor()
    {
        return 'Custom CMSMS Module';
    }

    public function GetAuthorEmail()
    {
        return '';
    }

    public function GetAdminDescription()
    {
        return 'Repeatable Easy Read page blocks with an image upload, alt description and WYSIWYG content.';
    }

    public function GetDescription()
    {
        return $this->GetAdminDescription();
    }

    public function GetHelp()
    {
        $file = cms_join_path($this->GetModulePath(), 'docs', 'help.inc');
        return is_file($file) ? file_get_contents($file) : '';
    }

    public function GetChangeLog()
    {
        $file = cms_join_path($this->GetModulePath(), 'docs', 'changelog.inc');
        return is_file($file) ? file_get_contents($file) : '';
    }

    public function HasAdmin()
    {
        return true;
    }

    public function GetAdminSection()
    {
        return 'extensions';
    }

    /**
     * This controls only the Extensions menu entry. Easy Read remains
     * available as a page content block for editors who can edit that page.
     */
    public function VisibleToAdminUser()
    {
        return $this->CheckPermission(self::VIEW_EXTENSION_PERMISSION);
    }

    public function IsPluginModule()
    {
        return true;
    }

    // A content-block provider must be available while page templates/pages are edited.
    public function LazyLoadAdmin()
    {
        return false;
    }

    public function LazyLoadFrontend()
    {
        return false;
    }

    public function HasCapability($capability, $params = array())
    {
        if ($capability === CmsCoreCapabilities::CONTENT_BLOCKS) return true;
        if ($capability === CmsCoreCapabilities::PLUGIN_MODULE) return true;
        return false;
    }

    /** Register the Design Manager template without replacing user edits. */
    public function InstallDesignTemplate()
    {
        try {
            $type = CmsLayoutTemplateType::load('EasyRead::summary');
        } catch (CmsDataNotFoundException $e) {
            $type = new CmsLayoutTemplateType();
            $type->set_originator($this->GetName());
            $type->set_name('summary');
            $type->set_dflt_flag(true);
            $type->set_content_callback('EasyRead::ResetSummaryTemplate');
            $type->reset_content_to_factory();
            $type->save();
        }
        // Repeated installation/upgrade must preserve existing templates.
        if (CmsLayoutTemplate::load_all_by_type($type)) return;
        $tpl = new CmsLayoutTemplate();
        $tpl->set_name(CmsLayoutTemplate::generate_unique_name('Easy Read Summary'));
        $tpl->set_owner(get_userid());
        $tpl->set_type($type);
        $tpl->set_type_dflt(true);
        $tpl->set_content(self::ResetSummaryTemplate($type));
        $tpl->save();
    }

    public static function ResetSummaryTemplate($type)
    {
        return file_get_contents(__DIR__.'/templates/default.tpl');
    }

    protected function InitializeFrontend()
    {
        parent::InitializeFrontend();
        $this->SetParameterType('data', CLEAN_NONE);
        $this->SetParameterType('to', CLEAN_STRING);
        $this->SetParameterType('template', CLEAN_STRING);
    }

    /**
     * Build the editor shown inside Content Manager for {content_module ...}.
     *
     * Current CMSMS calls this method for modules advertising the
     * CmsCoreCapabilities::CONTENT_BLOCKS capability.
     */
    public function GetContentBlockFieldInput($blockName, $value, $params, $adding, $contentObj)
    {
        $items = $this->decodeItems($value);
        if (!$items) {
            $items = array($this->newBlankItem());
        }

        $base = $this->baseKey($blockName);
        $editorPageId = (!$adding && is_object($contentObj)) ? (int)$contentObj->Id() : 0;
        $label = isset($params['label']) && trim((string)$params['label']) !== ''
            ? trim((string)$params['label'])
            : 'Easy Read';

        // Keep the complete current value in one real CMSMS content field.
        $hiddenValue = $this->encodeItems($items);
        $uploadUrl = html_entity_decode(
            $this->CreateLink('erupload', 'upload', '', '', array(), '', true),
            ENT_QUOTES,
            'UTF-8'
        );
        $uploadUrl .= (strpos($uploadUrl, '?') === false ? '?' : '&').'showtemplate=false';

        $out = array();
        $out[] = '<div class="easyread-editor" data-easyread-base="'.cms_htmlentities($base).'" data-easyread-block="'.cms_htmlentities($blockName).'" data-easyread-upload-url="'.cms_htmlentities($uploadUrl).'" data-easyread-page-id="'.$editorPageId.'">';
        $out[] = '<input type="hidden" class="easyread-json" name="'.cms_htmlentities($blockName).'" value="'.cms_htmlentities($hiddenValue).'">';
        $out[] = '<div class="easyread-editor-actions"><button type="button" class="easyread-toggle-all" aria-expanded="true" aria-controls="easyread-content-'.cms_htmlentities($base).'">Hide Easy Read</button></div>';
        $out[] = '<div class="easyread-editor-content" id="easyread-content-'.cms_htmlentities($base).'">';
        $out[] = '<div class="information"><p><strong>What is Easy Read?</strong> Easy Read uses simple words and helpful pictures to make information easier to understand.</p><ul><li>Keep sentences as short as possible. Use one idea per sentence.</li><li>Use everyday words. Avoid jargon and explain any difficult words.</li><li>Choose a clear photo or a familiar symbol. The picture should explain what the text beside it is saying and help the reader understand its meaning. Use one main subject and avoid busy backgrounds.</li><li>For suitable pictures, try <a href="https://www.photosymbols.com/" target="_blank" rel="noopener noreferrer">Photosymbols</a>. Check the licence and only use pictures you have permission to use.</li><li>Add a short alt description for each picture.</li></ul></div>';
        $out[] = '<div class="easyread-items">';

        foreach ($items as $item) {
            $out[] = $this->renderEditorItem($blockName, $base, $item);
        }

        $out[] = '</div>';
        $out[] = '<div class="easyread-add-wrap"><button type="button" class="easyread-add">+ Add Item</button></div>';
        $out[] = '</div>'; // .easyread-editor-content
        $out[] = '</div>';
        $out[] = $this->editorAssets();

        return array($label, implode("\n", $out));
    }

    /**
     * Convert the submitted editor controls back into the single value that
     * CMSMS stores on the page.
     */
    public function GetContentBlockFieldValue($blockName, $blockParams, $inputParams, $contentObj)
    {
        $this->contentBlockErrors[$blockName] = '';
        $base = $this->baseKey($blockName);

        $raw = isset($inputParams[$blockName]) ? $inputParams[$blockName] : '';
        $items = $this->decodeSubmittedItems($raw);
        if (!$items) return '[]';

        $result = array();
        foreach ($items as $item) {
            $id = $this->cleanItemId(isset($item['id']) ? $item['id'] : '');
            if ($id === '') $id = $this->makeItemId();

            $altName = $this->fieldName($base, 'alt', $id);
            $contentName = $this->fieldName($base, 'content', $id);
            $uploadName = $this->fieldName($base, 'upload', $id);

            $alt = isset($inputParams[$altName])
                ? trim((string)$inputParams[$altName])
                : trim((string)(isset($item['alt']) ? $item['alt'] : ''));

            // WYSIWYG HTML is page content, so deliberately do not strip tags here.
            $content = isset($inputParams[$contentName])
                ? (string)$inputParams[$contentName]
                : (string)(isset($item['content']) ? $item['content'] : '');

            $image = $this->cleanStoredImage(isset($item['image']) ? $item['image'] : '');

            $uploaded = false;
            if (isset($_FILES[$uploadName]) && is_array($_FILES[$uploadName])) {
                $file = $_FILES[$uploadName];
                $error = isset($file['error']) ? (int)$file['error'] : UPLOAD_ERR_NO_FILE;

                if ($error !== UPLOAD_ERR_NO_FILE) {
                    $uploadResult = $this->handleImageUpload($file);
                    if (isset($uploadResult['error'])) {
                        $this->contentBlockErrors[$blockName] = $uploadResult['error'];
                    } elseif (isset($uploadResult['image'])) {
                        $image = $uploadResult['image'];
                        $uploaded = true;
                    }
                }
            }

            // Empty rows are not stored. The editor automatically provides one
            // blank row again when the page is reopened.
            if ($image === '' && $alt === '' && trim(strip_tags($content)) === '') {
                continue;
            }

            $result[] = array(
                'id' => $id,
                'image' => $image,
                'alt' => $alt,
                'content' => $content,
            );
        }

        return $this->encodeItems($result);
    }

    /**
     * CMSMS calls this during page validation.
     */
    public function ValidateContentBlockFieldValue($blockName, $value, $blockParams, $contentObj)
    {
        if (!empty($this->contentBlockErrors[$blockName])) {
            return $this->contentBlockErrors[$blockName];
        }

        if ($value === '' || $value === null) return '';

        $decoded = json_decode((string)$value, true);
        if (!is_array($decoded)) {
            return 'Easy Read: the block data could not be read. Please reopen the page and try again.';
        }

        return '';
    }

    /**
     * Backwards-compatible aliases for older modules/content managers that used
     * the pre-2.x names. They do no harm on current CMSMS.
     */
    public function GetContentBlockInput($blockName, $value, $params, $adding, $contentObj)
    {
        return $this->GetContentBlockFieldInput($blockName, $value, $params, $adding, $contentObj);
    }

    public function GetContentBlockValue($blockName, $blockParams, $inputParams, $contentObj)
    {
        return $this->GetContentBlockFieldValue($blockName, $blockParams, $inputParams, $contentObj);
    }

    public function ValidateContentBlockValue($blockName, $value, $blockParams, $contentObj)
    {
        return $this->ValidateContentBlockFieldValue($blockName, $value, $blockParams, $contentObj);
    }

    /** Decode and normalise a saved JSON value. */
    public function decodeItems($value)
    {
        if (is_array($value)) $data = $value;
        else {
            $value = trim((string)$value);
            if ($value === '') return array();
            $data = json_decode($value, true);
        }

        if (!is_array($data)) return array();

        $out = array();
        foreach ($data as $row) {
            if (!is_array($row)) continue;
            $id = $this->cleanItemId(isset($row['id']) ? $row['id'] : '');
            if ($id === '') $id = $this->makeItemId();
            $out[] = array(
                'id' => $id,
                'image' => $this->cleanStoredImage(isset($row['image']) ? $row['image'] : ''),
                'alt' => isset($row['alt']) ? (string)$row['alt'] : '',
                'content' => isset($row['content']) ? (string)$row['content'] : '',
            );
        }
        return $out;
    }

    /** Decode editor JSON submitted by the CMSMS page editor. */
    private function decodeSubmittedItems($value)
    {
        if (is_array($value)) $data = $value;
        else {
            $value = trim((string)$value);
            if ($value === '') return array();
            $data = json_decode($value, true);
        }

        if (!is_array($data)) return array();

        $out = array();
        foreach ($data as $row) {
            if (!is_array($row)) continue;
            $id = $this->cleanItemId(isset($row['id']) ? $row['id'] : '');
            if ($id === '') $id = $this->makeItemId();
            $out[] = array(
                'id' => $id,
                'image' => $this->cleanStoredImage(isset($row['image']) ? $row['image'] : ''),
                'alt' => isset($row['alt']) ? (string)$row['alt'] : '',
                'content' => isset($row['content']) ? (string)$row['content'] : '',
            );
        }
        return $out;
    }

    public function encodeItems($items)
    {
        $json = json_encode(array_values($items), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $json === false ? '[]' : $json;
    }

    /** Turn stored image data into frontend-friendly data. */
    public function prepareFrontendItems($value)
    {
        $config = cms_config::get_instance();
        $uploadsUrl = rtrim($config['uploads_url'], '/');
        $items = $this->decodeItems($value);

        foreach ($items as &$item) {
            $item['image_path'] = $item['image'] !== '' ? 'uploads/'.$item['image'] : '';
            $item['image_url'] = $item['image'] !== '' ? $uploadsUrl.'/'.$this->urlPath($item['image']) : '';
            // Easy Read content is stored as WYSIWYG HTML.  Render it once here
            // so module tags, such as {YouTubeCatalog}, work in Design Manager
            // templates without requiring {eval} in every template.
            $item['content'] = $this->renderFrontendContent($item['content']);
        }
        unset($item);

        return $items;
    }

    private function renderFrontendContent($content)
    {
        $content = (string)$content;
        if ($content === '' || strpos($content, '{') === false) return $content;

        try {
            $smarty = cmsms()->GetSmarty();
            $template = $smarty->CreateTemplate('string:'.$content, null, null, $smarty);
            return $template->fetch();
        } catch (Exception $e) {
            // Preserve ordinary editor content if a tag is incomplete or a
            // third-party module is unavailable.
            return $content;
        }
    }

    private function renderEditorItem($blockName, $base, $item)
    {
        $id = $this->cleanItemId($item['id']);
        if ($id === '') $id = $this->makeItemId();

        $altName = $this->fieldName($base, 'alt', $id);
        $contentName = $this->fieldName($base, 'content', $id);
        $uploadName = $this->fieldName($base, 'upload', $id);
        $textareaId = $contentName;

        $image = $this->cleanStoredImage($item['image']);
        $previewUrl = $image !== '' ? $this->storedImageUrl($image) : '';

        $textarea = CmsFormUtils::create_textarea(array(
            'name' => $contentName,
            'id' => $textareaId,
            'class' => 'cms_textarea easyread-wysiwyg',
            'value' => $item['content'],
            'enablewysiwyg' => true,
            'rows' => 8,
            'cols' => 80,
        ));

        $out = array();
        $out[] = '<div class="easyread-item" draggable="false" data-easyread-id="'.cms_htmlentities($id).'" data-easyread-image="'.cms_htmlentities($image).'" data-easyread-image-url="'.cms_htmlentities($previewUrl).'">';
        $out[] = '  <div class="easyread-item-toolbar">';
        $out[] = '    <button type="button" class="easyread-drag" title="Drag to reorder" aria-label="Drag this Easy Read item to reorder">&#x2630;</button>';
        $out[] = '    <span class="easyread-item-number" aria-hidden="true"></span>';
        $out[] = '    <span class="easyread-toolbar-spacer"></span>';
        $out[] = '    <button type="button" class="easyread-up" title="Move up">Move up</button>';
        $out[] = '    <button type="button" class="easyread-down" title="Move down">Move down</button>';
        $out[] = '    <button type="button" class="easyread-delete">Delete Item</button>';
        $out[] = '  </div>';

        $out[] = '  <div class="easyread-item-body">';
        $out[] = '    <div class="easyread-media-column">';
        $out[] = '      <span class="easyread-field-label">Picture:</span>';
        $out[] = '      <div class="easyread-image-frame">';
        $out[] = '        <img class="easyread-preview'.($previewUrl === '' ? ' is-hidden' : '').'" src="'.cms_htmlentities($previewUrl).'" alt="">';
        $out[] = '        <span class="easyread-image-placeholder'.($previewUrl !== '' ? ' is-hidden' : '').'">No picture</span>';
        $out[] = '        <label class="easyread-image-edit" title="'.($image === '' ? 'Add picture' : 'Change picture').'">';
        $out[] = '          <input class="easyread-upload" type="file" name="'.cms_htmlentities($uploadName).'" accept="image/jpeg,image/png,image/webp,image/gif">';
        $out[] = '          <span class="easyread-image-edit-symbol" aria-hidden="true">'.($image === '' ? '+' : '&#x270E;').'</span>';
        $out[] = '          <span class="easyread-visually-hidden easyread-image-edit-text">'.($image === '' ? 'Add picture' : 'Change picture').'</span>';
        $out[] = '        </label>';
        $out[] = '        <button type="button" class="easyread-remove-image" title="Delete picture" aria-label="Delete picture"'.($image === '' ? ' disabled' : '').'>';
        $out[] = '          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v6m4-6v6"/></svg>';
        $out[] = '        </button>';
        $out[] = '      </div>';
        $out[] = '      <span class="easyread-upload-status" role="status" aria-live="polite"></span>';
        $out[] = '    </div>';
        $out[] = '    <div class="easyread-copy-column">';
        $out[] = '      <div class="easyread-field">';
        $out[] = '        <label for="'.cms_htmlentities($altName).'">Alt Description:</label>';
        $out[] = '        <input class="easyread-alt" type="text" id="'.cms_htmlentities($altName).'" name="'.cms_htmlentities($altName).'" value="'.cms_htmlentities($item['alt']).'" maxlength="500">';
        $out[] = '      </div>';
        $out[] = '      <div class="easyread-field easyread-content-field">';
        $out[] = '        <label for="'.cms_htmlentities($textareaId).'">Content:</label>';
        $out[] = $textarea;
        $out[] = '      </div>';
        $out[] = '    </div>';
        $out[] = '  </div>';
        $out[] = '</div>';

        return implode("\n", $out);
    }

    private function editorAssets()
    {
        static $done = false;
        if ($done) return '';
        $done = true;

        // Deliberately neutral colours so the controls work with light and dark
        // CMSMS admin themes.
        return <<<'HTML'
<style>
.easyread-editor{width:100%;box-sizing:border-box}
.easyread-source-controls{margin-top:.6rem}
.easyread-link-fields{margin-top:.6rem}
.easyread-link-fields[hidden]{display:none!important}
.easyread-link-fields input{width:100%;box-sizing:border-box;margin:.3rem 0}
.easyread-link-fields small{display:block;margin-top:.4rem}
.easyread-editor-actions{display:flex;justify-content:flex-start;margin:0 0 .7rem}
.easyread-toggle-all{white-space:nowrap}
.easyread-editor-content{display:block}
.easyread-editor.easyread-all-collapsed .easyread-editor-content{display:none}

.easyread-items{display:flex;flex-direction:column}
.easyread-item{position:relative;margin:0 0 1rem;padding:1rem;border:1px solid rgba(127,127,127,.35);border-radius:.25rem;background:transparent;color:inherit;box-sizing:border-box}
.easyread-item.easyread-dragging{opacity:.55}
.easyread-item-toolbar{display:flex;align-items:center;gap:.45rem;margin:-.25rem 0 .8rem}
.easyread-toolbar-spacer{flex:1 1 auto}
.easyread-drag{cursor:grab;font-size:1.15rem;line-height:1;padding:.35rem .55rem}
.easyread-drag:active{cursor:grabbing}
.easyread-item-number{font-weight:600}
.easyread-item-body{display:grid;grid-template-columns:minmax(210px,300px) minmax(0,1fr);gap:1.25rem;align-items:start}
.easyread-media-column,.easyread-copy-column{min-width:0}
.easyread-field{margin:0 0 .9rem}
.easyread-content-field{margin-bottom:0}
.easyread-field>label{display:block;font-weight:600;margin:0 0 .35rem}
.easyread-field-label{display:block;font-weight:600;margin:0 0 .35rem}
.easyread-alt{display:block;width:100%;box-sizing:border-box}
.easyread-image-frame{position:relative;width:100%;aspect-ratio:4/3;min-height:150px;overflow:hidden;border:1px solid rgba(127,127,127,.35);border-radius:.35rem;background:rgba(127,127,127,.07)}
.easyread-preview{display:block;width:100%;height:100%;object-fit:contain}
.easyread-preview.is-hidden{display:none}
.easyread-image-placeholder{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:inherit;opacity:.65;font-size:.92rem}
.easyread-image-placeholder.is-hidden{display:none}
.easyread-image-edit,.easyread-remove-image{position:absolute;display:flex;align-items:center;justify-content:center;width:2.35rem;height:2.35rem;margin:0;padding:0;border:1px solid rgba(0,0,0,.25);border-radius:50%;background:#fff;color:#222;box-shadow:0 1px 5px rgba(0,0,0,.25);cursor:pointer;box-sizing:border-box}
.easyread-image-edit{top:.55rem;left:.55rem}
.easyread-remove-image{right:.55rem;bottom:.55rem}
.easyread-image-edit:hover,.easyread-image-edit:focus-within,.easyread-remove-image:hover:not(:disabled),.easyread-remove-image:focus{background:#f1f1f1}
.easyread-image-edit-symbol{font-size:1.55rem;line-height:1;font-weight:700}
.easyread-remove-image svg{width:1.25rem;height:1.25rem;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.easyread-remove-image:disabled{display:none}
.easyread-upload{position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important}
.easyread-visually-hidden{position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important}
.easyread-upload-status{display:block;min-height:1.3em;margin-top:.35rem;font-size:.92rem}
.easyread-upload-status.is-error{color:#b00020;font-weight:600}
.easyread-upload-status.is-success{color:#237804;font-weight:600}
.easyread-item textarea{width:100%;box-sizing:border-box}
.easyread-add-wrap{text-align:right;margin:.5rem 0 1rem}
.easyread-image-frame.easyread-file-dragover{outline:3px dashed #2878b8;outline-offset:-4px;background:rgba(40,120,184,.12)}
.easyread-drop-before{box-shadow:0 -3px 0 currentColor}
.easyread-drop-after{box-shadow:0 3px 0 currentColor}
@media (max-width:760px){.easyread-item{padding:.75rem}.easyread-item-toolbar{align-items:flex-start;flex-wrap:wrap}.easyread-toolbar-spacer{display:none}.easyread-up,.easyread-down,.easyread-delete{font-size:.9rem}.easyread-item-body{grid-template-columns:1fr;gap:.85rem}.easyread-media-column{width:100%;max-width:24rem}.easyread-image-frame{min-height:180px}.easyread-copy-column{width:100%}}
</style>
<script>
(function(){
  'use strict';

  function escHtml(value){
    return String(value || '').replace(/[&<>'"]/g,function(ch){
      return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[ch];
    });
  }

  function newId(){
    return 'er_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2,10);
  }

  function markChanged(){
    if (window.jQuery) {
      window.jQuery(document).trigger('cmsms_formchange');
    } else {
      try { document.dispatchEvent(new CustomEvent('cmsms_formchange')); } catch(e) {}
    }
  }

  function fieldName(base,type,id){
    return 'easyread_' + type + '_' + base + '_' + id;
  }

  function editorContent(textarea){
    if (window.tinymce) {
      var ed = window.tinymce.get(textarea.id);
      if (ed) return ed.getContent();
    }
    return textarea.value || '';
  }

  function firstTinyEditor(){
    if (!window.tinymce) return null;
    if (window.tinymce.activeEditor) return window.tinymce.activeEditor;
    if (typeof window.tinymce.get === 'function') {
      var list = window.tinymce.get();
      if (list && list.length) return list[0];
    }
    if (window.tinymce.editors && window.tinymce.editors.length) return window.tinymce.editors[0];
    return null;
  }

  function optionFromEditor(editor,name){
    if (!editor) return undefined;
    if (editor.options && typeof editor.options.get === 'function') {
      try { return editor.options.get(name); } catch(e) {}
    }
    if (editor.settings && editor.settings[name] !== undefined) return editor.settings[name];
    return undefined;
  }

  function copyEditorSettings(editor){
    var settings = {};
    var src = (editor && editor.settings) ? editor.settings : {};
    Object.keys(src).forEach(function(key){
      if (key !== 'target' && key !== 'selector' && key !== 'elements' && key !== 'id') {
        settings[key] = src[key];
      }
    });
    [
      'license_key','plugins','toolbar','toolbar1','toolbar2','menubar','height',
      'min_height','width','skin','content_css','content_style','language',
      'promotion','branding','statusbar','resize','browser_spellcheck',
      'entity_encoding','convert_urls','relative_urls','remove_script_host',
      'document_base_url','valid_elements','extended_valid_elements',
      'paste_as_text','file_picker_callback','automatic_uploads','setup',
      'init_instance_callback','forced_root_block','newline_behavior'
    ].forEach(function(key){
      var value = optionFromEditor(editor,key);
      if (value !== undefined && value !== null) settings[key] = value;
    });
    if (!settings.license_key) settings.license_key = 'gpl';
    settings.promotion = false;
    return settings;
  }

  function editorInitOptions(root,textarea){
    var saved = root._easyReadEditorSettings || {};
    var settings = Object.assign({},saved);
    settings.selector = '#' + textarea.id;
    settings.license_key = settings.license_key || 'gpl';
    settings.promotion = false;
    delete settings.target;
    delete settings.elements;
    delete settings.id;
    return settings;
  }

  function rememberEditor(root,textarea,editor){
    if (editor && !root._easyReadEditorSettings) {
      root._easyReadEditorSettings = copyEditorSettings(editor);
    }
    if (!root._easyReadEditorClasses && textarea) {
      root._easyReadEditorClasses = Array.prototype.filter.call(textarea.classList,function(name){
        return !/^easyread_content_/.test(name);
      });
    }
  }

  function applyEditorClasses(root,textarea){
    if (!textarea || !root._easyReadEditorClasses) return;
    root._easyReadEditorClasses.forEach(function(name){
      if (name) textarea.classList.add(name);
    });
  }

  function orderedItems(root){
    return Array.prototype.slice.call(root.querySelectorAll('.easyread-item'))
      .map(function(item,index){
        var order = parseInt(item.dataset.easyreadOrder,10);
        return { item:item, order:isNaN(order) ? index : order, index:index };
      })
      .sort(function(a,b){
        return (a.order - b.order) || (a.index - b.index);
      })
      .map(function(entry){
        return entry.item;
      });
  }

  function applyItemOrder(items){
    items.forEach(function(item,index){
      item.dataset.easyreadOrder = String(index);
      item.style.order = String(index);
    });
  }

  function updateNumbers(root){
    var items = orderedItems(root);
    items.forEach(function(item,index){
      var el = item.querySelector('.easyread-item-number');
      if (el) el.textContent = 'Item ' + (index + 1);
      var up = item.querySelector('.easyread-up');
      var down = item.querySelector('.easyread-down');
      if (up) up.disabled = index === 0;
      if (down) down.disabled = index === items.length - 1;
    });
  }

  function sync(root){
    var hidden = root.querySelector('.easyread-json');
    if (!hidden) return;
    var data = [];
    orderedItems(root).forEach(function(item){
      var textarea = item.querySelector('.easyread-wysiwyg');
      data.push({
        id: item.dataset.easyreadId || newId(),
        image: item.dataset.easyreadImage || '',
        alt: (item.querySelector('.easyread-alt') || {}).value || '',
        content: textarea ? editorContent(textarea) : ''
      });
    });
    hidden.value = JSON.stringify(data);
    updateNumbers(root);
  }

  function removeEditor(item){
    if (item._easyReadUploadXhr) {
      try { item._easyReadUploadXhr.abort(); } catch(e) {}
    }
    var ta = item.querySelector('.easyread-wysiwyg');
    if (ta && window.tinymce) {
      try {
        var ed = window.tinymce.get(ta.id);
        if (ed) ed.remove();
      } catch(e) {
        // Do not let a WYSIWYG cleanup error stop the row being deleted.
      }
    }
  }

  function pendingUploads(form,change){
    if (!form) return 0;
    var count = Math.max(0,(form._easyReadPendingUploads || 0) + change);
    form._easyReadPendingUploads = count;
    form.querySelectorAll('[name$="apply"],[name$="submit"]').forEach(function(control){
      if (count > 0) {
        if (!control.disabled) {
          control.disabled = true;
          control.dataset.easyreadUploadDisabled = '1';
        }
      } else if (control.dataset.easyreadUploadDisabled === '1') {
        control.disabled = false;
        delete control.dataset.easyreadUploadDisabled;
      }
    });
    return count;
  }

  function uploadStatus(item,message,type){
    var status = item.querySelector('.easyread-upload-status');
    if (!status) return;
    status.textContent = message || '';
    status.classList.remove('is-error','is-success');
    if (type) status.classList.add('is-' + type);
  }

  function restoreStoredPreview(item){
    var preview = item.querySelector('.easyread-preview');
    if (!preview) return;
    var url = item.dataset.easyreadImageUrl || '';
    preview.src = url;
    preview.classList.toggle('is-hidden',!url);
    updateImageControls(item);
  }

  function updateImageControls(item){
    var preview = item.querySelector('.easyread-preview');
    var placeholder = item.querySelector('.easyread-image-placeholder');
    var edit = item.querySelector('.easyread-image-edit');
    var symbol = item.querySelector('.easyread-image-edit-symbol');
    var text = item.querySelector('.easyread-image-edit-text');
    var hasPicture = !!(preview && !preview.classList.contains('is-hidden') && preview.getAttribute('src'));
    var label = hasPicture ? 'Change picture' : 'Add picture';
    if (placeholder) placeholder.classList.toggle('is-hidden',hasPicture);
    if (edit) edit.title = label;
    if (symbol) symbol.textContent = hasPicture ? '✎' : '+';
    if (text) text.textContent = label;
  }

  function uploadImage(root,item,input,file,imageLink){
    if (item._easyReadUploadXhr) return;
    var url = root.dataset.easyreadUploadUrl || '';
    var form = root.closest('form');
    var removeButton = item.querySelector('.easyread-remove-image');
    if (!url) {
      input.value = '';
      restoreStoredPreview(item);
      uploadStatus(item,'Picture upload is unavailable. Reload the page and try again.','error');
      return;
    }

    if (file && file.type && !/^image\/(jpeg|png|gif|webp)$/i.test(file.type)) {
      input.value = '';
      restoreStoredPreview(item);
      uploadStatus(item,'Please choose a JPG, PNG, GIF or WebP picture.','error');
      return;
    }
    if (file && (file.size <= 0 || file.size > 8388608)) {
      input.value = '';
      restoreStoredPreview(item);
      uploadStatus(item,'Pictures must be smaller than 8 MB.','error');
      return;
    }

    var data = new FormData();
    if (imageLink) data.append('eruploadimage_url',imageLink);
    else data.append('eruploadfile',file,file.name);
    var xhr = new XMLHttpRequest();
    item._easyReadUploadXhr = xhr;
    input.disabled = true;
    item.querySelectorAll('.easyread-source-controls button').forEach(function(b){ b.disabled = true; });
    if (removeButton) removeButton.disabled = true;
    pendingUploads(form,1);
    uploadStatus(item,imageLink ? 'Copying picture…' : 'Uploading picture…','');

    var finished = false;
    function finish(){
      if (finished) return;
      finished = true;
      item._easyReadUploadXhr = null;
      input.disabled = false;
      item.querySelectorAll('.easyread-source-controls button').forEach(function(b){ b.disabled = false; });
      pendingUploads(form,-1);
    }

    xhr.open('POST',url,true);
    xhr.setRequestHeader('Accept','application/json');
    xhr.onreadystatechange = function(){
      if (xhr.readyState !== 4) return;
      var result = null;
      try { result = JSON.parse(xhr.responseText || ''); } catch(e) {}

      if (xhr.status >= 200 && xhr.status < 300 && result && result.success && result.image && result.url) {
        if (item.dataset.easyreadPreviewUrl) {
          try { URL.revokeObjectURL(item.dataset.easyreadPreviewUrl); } catch(e) {}
          item.dataset.easyreadPreviewUrl = '';
        }
        item.dataset.easyreadImage = result.image;
        item.dataset.easyreadImageUrl = result.url;
        input.value = '';
        restoreStoredPreview(item);
        if (removeButton) removeButton.disabled = false;
        uploadStatus(item,'Picture uploaded. Apply or Submit to save the page.','success');
        sync(root);
        markChanged();
      } else {
        input.value = '';
        restoreStoredPreview(item);
        if (removeButton) removeButton.disabled = !(item.dataset.easyreadImage || '');
        uploadStatus(item,(result && result.error) ? result.error : 'The picture could not be uploaded. Please try again.','error');
      }
      finish();
    };
    xhr.onerror = function(){
      input.value = '';
      restoreStoredPreview(item);
      if (removeButton) removeButton.disabled = !(item.dataset.easyreadImage || '');
      uploadStatus(item,'The picture could not be uploaded. Please try again.','error');
      finish();
    };
    xhr.onabort = finish;
    xhr.send(data);
  }

  function prepareForm(form){
    if (!form) return;

    // Pull the latest text out of TinyMCE/MicroTiny before CMSMS handles
    // Apply, Submit, Preview, etc.  Content Manager can intercept an Apply
    // button click before a normal browser submit event occurs.
    if (window.tinymce) {
      try { window.tinymce.triggerSave(); } catch(e) {}
    }

    form.querySelectorAll('.easyread-editor').forEach(function(editor){
      sync(editor);
    });
  }

  function bindWysiwyg(root,textarea){
    if (!textarea || !window.tinymce) return;

    var attempts = 0;
    var timer = window.setInterval(function(){
      attempts++;
      var ed = null;
      try { ed = window.tinymce.get(textarea.id); } catch(e) {}

      if (ed) {
        window.clearInterval(timer);
        textarea._easyReadInitPending = false;
        rememberEditor(root,textarea,ed);
        if (!ed._easyReadBound) {
          ed._easyReadBound = true;
          var changed = function(){ sync(root); markChanged(); };
          try { ed.on('change input keyup undo redo SetContent blur', changed); } catch(e) {}
        }
      } else if (attempts >= 40) {
        window.clearInterval(timer);
        textarea._easyReadInitPending = false;
      }
    },100);
  }

  function initEditorFromSavedSettings(root,textarea,attempt){
    if (!root || !textarea || !window.tinymce || window.tinymce.get(textarea.id)) return;
    if (textarea._easyReadInitPending) return;
    attempt = attempt || 0;
    applyEditorClasses(root,textarea);
    var source = firstTinyEditor();
    if (source) rememberEditor(root,source.getElement ? source.getElement() : textarea,source);
    var saved = root._easyReadEditorSettings;
    if (!saved || !saved.license_key) {
      if (attempt < 40 && !saved) {
        window.setTimeout(function(){initEditorFromSavedSettings(root,textarea,attempt + 1);},100);
        return;
      }
    }

    var settings = editorInitOptions(root,textarea);
    textarea._easyReadInitPending = true;
    try {
      var pending = window.tinymce.init(settings);
      if (pending && typeof pending.then === 'function') {
        pending.then(function(){},function(){ textarea._easyReadInitPending = false; });
      }
      bindWysiwyg(root,textarea);
    } catch(e) {
      textarea._easyReadInitPending = false;
    }
  }

  function initDynamicEditor(root,textarea,attempt){
    if (!root || !textarea) return;
    attempt = attempt || 0;
    applyEditorClasses(root,textarea);

    if (!window.tinymce) {
      if (attempt < 40) {
        window.setTimeout(function(){initDynamicEditor(root,textarea,attempt + 1);},100);
      }
      return;
    }
    if (window.tinymce.get(textarea.id) || textarea._easyReadInitPending) return;

    var source = firstTinyEditor();
    if (source) rememberEditor(root,source.getElement ? source.getElement() : textarea,source);
    if (!root._easyReadEditorSettings && attempt < 40) {
      window.setTimeout(function(){initDynamicEditor(root,textarea,attempt + 1);},100);
      return;
    }

    initEditorFromSavedSettings(root,textarea,0);
  }

  function moveItem(root,item,target,after){
    if (!item || !target || item === target) return;
    var items = orderedItems(root).filter(function(candidate){return candidate !== item;});
    var targetIndex = items.indexOf(target);
    if (targetIndex < 0) return;
    items.splice(targetIndex + (after ? 1 : 0),0,item);
    applyItemOrder(items);
    sync(root);
    markChanged();
  }

  function addItem(root){
    var base = root.dataset.easyreadBase;
    var id = newId();
    var altName = fieldName(base,'alt',id);
    var contentName = fieldName(base,'content',id);
    var uploadName = fieldName(base,'upload',id);
    var item = document.createElement('div');
    item.className = 'easyread-item';
    item.setAttribute('draggable','false');
    item.dataset.easyreadId = id;
    item.dataset.easyreadImage = '';
    item.dataset.easyreadImageUrl = '';
    item.dataset.easyreadOrder = String(orderedItems(root).length);
    item.style.order = item.dataset.easyreadOrder;
    item.innerHTML = ''+
      '<div class="easyread-item-toolbar">'+
        '<button type="button" class="easyread-drag" title="Drag to reorder" aria-label="Drag this Easy Read item to reorder">&#x2630;</button>'+
        '<span class="easyread-item-number" aria-hidden="true"></span>'+
        '<span class="easyread-toolbar-spacer"></span>'+
        '<button type="button" class="easyread-up" title="Move up">Move up</button>'+
        '<button type="button" class="easyread-down" title="Move down">Move down</button>'+
        '<button type="button" class="easyread-delete">Delete Item</button>'+
      '</div>'+
      '<div class="easyread-item-body">'+
        '<div class="easyread-media-column">'+
          '<span class="easyread-field-label">Picture:</span>'+
          '<div class="easyread-image-frame">'+
          '<img class="easyread-preview is-hidden" src="" alt="">'+
            '<span class="easyread-image-placeholder">No picture</span>'+
            '<label class="easyread-image-edit" title="Add picture">'+
            '<input class="easyread-upload" type="file" name="'+escHtml(uploadName)+'" accept="image/jpeg,image/png,image/webp,image/gif">'+
              '<span class="easyread-image-edit-symbol" aria-hidden="true">+</span>'+
              '<span class="easyread-visually-hidden easyread-image-edit-text">Add picture</span>'+
            '</label>'+
            '<button type="button" class="easyread-remove-image" title="Delete picture" aria-label="Delete picture" disabled>'+
              '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v6m4-6v6"/></svg>'+
            '</button>'+
          '</div>'+
          '<span class="easyread-upload-status" role="status" aria-live="polite"></span>'+
        '</div>'+
        '<div class="easyread-copy-column">'+
          '<div class="easyread-field">'+
            '<label for="'+escHtml(altName)+'">Alt Description:</label>'+
            '<input class="easyread-alt" type="text" id="'+escHtml(altName)+'" name="'+escHtml(altName)+'" value="" maxlength="500">'+
          '</div>'+
          '<div class="easyread-field easyread-content-field">'+
            '<label for="'+escHtml(contentName)+'">Content:</label>'+
            '<textarea class="cms_textarea easyread-wysiwyg" data-cms-lang="html" id="'+escHtml(contentName)+'" name="'+escHtml(contentName)+'" cols="80" rows="8"></textarea>'+
          '</div>'+
        '</div>'+
      '</div>';
    root.querySelector('.easyread-items').appendChild(item);
    bindItem(root,item);
    sync(root);
    markChanged();
    setTimeout(function(){ var ta = item.querySelector('.easyread-wysiwyg'); applyEditorClasses(root,ta); initDynamicEditor(root,ta); },0);
    item.scrollIntoView({behavior:'smooth',block:'nearest'});
  }

  function bindItem(root,item){
    var handle = item.querySelector('.easyread-drag');
    var del = item.querySelector('.easyread-delete');
    var up = item.querySelector('.easyread-up');
    var down = item.querySelector('.easyread-down');
    var alt = item.querySelector('.easyread-alt');
    var upload = item.querySelector('.easyread-upload');
    var removeImage = item.querySelector('.easyread-remove-image');
    var textarea = item.querySelector('.easyread-wysiwyg');
    // Created here for both existing rows and newly added rows.
    if (upload && !item.querySelector('.easyread-source-controls')) {
      var controls = document.createElement('div');
      controls.className = 'easyread-source-controls';
      controls.innerHTML = '<button type="button" class="easyread-choose-file">Upload image</button> '+
        '<button type="button" class="easyread-choose-link" aria-expanded="false">Use image link +</button>'+
        '<div class="easyread-link-fields" hidden><label>Image link <input type="url" class="easyread-image-link" placeholder="https://example.com/picture.png"></label> '+
        '<button type="button" class="easyread-import-link">Import image</button><small>Paste a direct JPG, PNG, GIF or WebP image link. Maximum 8 MB.</small></div>';
      item.querySelector('.easyread-media-column').appendChild(controls);
      var linkFields = controls.querySelector('.easyread-link-fields');
      var chooseLink = controls.querySelector('.easyread-choose-link');
      controls.querySelector('.easyread-choose-file').addEventListener('click',function(){ upload.click(); });
      chooseLink.addEventListener('click',function(){
        linkFields.hidden = !linkFields.hidden;
        chooseLink.setAttribute('aria-expanded',String(!linkFields.hidden));
        chooseLink.textContent = linkFields.hidden ? 'Use image link +' : 'Use image link −';
        if (!linkFields.hidden) controls.querySelector('input').focus();
      });
      controls.querySelector('.easyread-import-link').addEventListener('click',function(){
        var link = controls.querySelector('input').value.trim();
        if (!/^https?:\/\//i.test(link)) {
          uploadStatus(item,'Paste a direct image link starting with https:// or http://.','error');
          return;
        }
        uploadImage(root,item,upload,null,link);
      });
      controls.querySelector('input').addEventListener('keydown',function(e){
        if (e.key === 'Enter') { e.preventDefault(); controls.querySelector('.easyread-import-link').click(); }
      });
    }

    var imageFrame = item.querySelector('.easyread-image-frame');
    if (imageFrame && upload) {
      imageFrame.title = 'Drop an image file here, or use Add picture';
      function isFileDrag(e){
        return e.dataTransfer && Array.prototype.indexOf.call(e.dataTransfer.types || [], 'Files') !== -1;
      }
      imageFrame.addEventListener('dragenter',function(e){
        if (!isFileDrag(e)) return;
        e.preventDefault(); e.stopPropagation();
        imageFrame.classList.add('easyread-file-dragover');
      });
      imageFrame.addEventListener('dragover',function(e){
        if (!isFileDrag(e)) return;
        e.preventDefault(); e.stopPropagation();
        e.dataTransfer.dropEffect = item._easyReadUploadXhr ? 'none' : 'copy';
        imageFrame.classList.add('easyread-file-dragover');
      });
      imageFrame.addEventListener('dragleave',function(e){
        if (!imageFrame.contains(e.relatedTarget)) imageFrame.classList.remove('easyread-file-dragover');
      });
      imageFrame.addEventListener('drop',function(e){
        imageFrame.classList.remove('easyread-file-dragover');
        if (!isFileDrag(e) && !(e.dataTransfer && e.dataTransfer.files.length)) return;
        e.preventDefault(); e.stopPropagation();
        if (item._easyReadUploadXhr) {
          uploadStatus(item,'Please wait for the current picture to finish uploading.','');
          return;
        }
        var files = e.dataTransfer.files;
        if (files.length !== 1) {
          uploadStatus(item,'Please drop one image file at a time.','error');
          return;
        }
        uploadImage(root,item,upload,files[0]);
      });
    }

    if (handle) {
      handle.addEventListener('mousedown',function(){ item.setAttribute('draggable','true'); });
      handle.addEventListener('touchstart',function(){ item.setAttribute('draggable','true'); },{passive:true});
      handle.addEventListener('mouseup',function(){ setTimeout(function(){ item.setAttribute('draggable','false'); },0); });
    }

    item.addEventListener('dragstart',function(e){
      if (!item.getAttribute('draggable') || item.getAttribute('draggable') === 'false') { e.preventDefault(); return; }
      item.classList.add('easyread-dragging');
      e.dataTransfer.effectAllowed = 'move';
      try { e.dataTransfer.setData('text/plain',item.dataset.easyreadId); } catch(err) {}
    });

    item.addEventListener('dragend',function(){
      item.classList.remove('easyread-dragging');
      item.setAttribute('draggable','false');
      root._easyReadDropTarget = null;
      root._easyReadDropAfter = false;
      root.querySelectorAll('.easyread-item').forEach(function(x){x.classList.remove('easyread-drop-before','easyread-drop-after');});
      sync(root); markChanged();
    });

    if (del) del.addEventListener('click',function(){
      removeEditor(item);
      item.remove();
      if (!root.querySelector('.easyread-item')) addItem(root);
      applyItemOrder(orderedItems(root));
      sync(root); markChanged();
    });

    if (up) up.addEventListener('click',function(){
      var items = orderedItems(root);
      var index = items.indexOf(item);
      var prev = index > 0 ? items[index - 1] : null;
      if (prev) moveItem(root,item,prev,false);
    });

    if (down) down.addEventListener('click',function(){
      var items = orderedItems(root);
      var index = items.indexOf(item);
      var next = index >= 0 && index < items.length - 1 ? items[index + 1] : null;
      if (next) moveItem(root,item,next,true);
    });

    if (alt) {
      alt.addEventListener('input',function(){sync(root);markChanged();});
      alt.addEventListener('change',function(){sync(root);markChanged();});
    }
    if (textarea) {
      textarea.addEventListener('input',function(){sync(root);markChanged();});
      textarea.addEventListener('change',function(){sync(root);markChanged();});
      textarea.addEventListener('blur',function(){sync(root);});
      bindWysiwyg(root,textarea);
    }

    if (upload) upload.addEventListener('change',function(){
      var file = upload.files && upload.files[0];
      var preview = item.querySelector('.easyread-preview');
      var btn = item.querySelector('.easyread-remove-image');

      // Only use a temporary object URL for the on-screen preview.  Never put
      // the image bytes/base64 into the hidden JSON field: doing that can make
      // the CMSMS page request huge and cause HTTP 413 Request Entity Too Large.
      if (item.dataset.easyreadPreviewUrl) {
        try { URL.revokeObjectURL(item.dataset.easyreadPreviewUrl); } catch(e) {}
        item.dataset.easyreadPreviewUrl = '';
      }

      if (file && preview && /^image\//.test(file.type)) {
        var previewUrl = '';
        try { previewUrl = URL.createObjectURL(file); } catch(e) {}
        if (previewUrl) {
          item.dataset.easyreadPreviewUrl = previewUrl;
          preview.src = previewUrl;
          preview.classList.remove('is-hidden');
          updateImageControls(item);
        }
        if (btn) btn.disabled = false;
      }

      sync(root);
      markChanged();
      if (file) uploadImage(root,item,upload,file);
    });

    if (removeImage) removeImage.addEventListener('click',function(){
      item.dataset.easyreadImage = '';
      if (item.dataset.easyreadPreviewUrl) {
        try { URL.revokeObjectURL(item.dataset.easyreadPreviewUrl); } catch(e) {}
        item.dataset.easyreadPreviewUrl = '';
      }
      if (upload) upload.value = '';
      var preview = item.querySelector('.easyread-preview');
      if (preview) { preview.src=''; preview.classList.add('is-hidden'); }
      removeImage.disabled = true;
      item.dataset.easyreadImageUrl = '';
      updateImageControls(item);
      uploadStatus(item,'','');
      sync(root); markChanged();
    });
  }

  function bindRoot(root){
    if (root.dataset.easyreadBound === '1') return;
    root.dataset.easyreadBound = '1';
    var toggleAll = root.querySelector('.easyread-toggle-all');
    var pageId = Number(root.dataset.easyreadPageId || 0);
    // New unsaved pages do not share a preference. Each saved page/block has its own cookie.
    var cookieName = pageId > 0 ? 'easyreadEditor_' + pageId + '_' + root.dataset.easyreadBase : '';
    var cookiePath = window.location.pathname.replace(/[^/]*$/, '') || '/';
    function setEditorCollapsed(collapsed, save){
      root.classList.toggle('easyread-all-collapsed', collapsed);
      if (toggleAll) {
        toggleAll.textContent = collapsed ? 'Show Easy Read' : 'Hide Easy Read';
        toggleAll.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
      }
      if (save && cookieName) {
        try {
          document.cookie = cookieName + '=' + (collapsed ? 'hidden' : 'shown') +
            '; Path=' + cookiePath + '; Max-Age=31536000; SameSite=Lax' +
            (window.location.protocol === 'https:' ? '; Secure' : '');
        } catch (error) { /* Keep the editor usable when cookies are blocked. */ }
      }
    }
    var savedCollapsed = false;
    if (cookieName) {
      try {
        document.cookie.split(';').some(function(cookie){
          var pair = cookie.trim().split('=');
          if (pair[0] !== cookieName) return false;
          savedCollapsed = pair[1] === 'hidden';
          return true;
        });
      } catch (error) { /* Default to showing the editor. */ }
    }
    setEditorCollapsed(savedCollapsed, false);
    if (toggleAll) toggleAll.addEventListener('click',function(){
      setEditorCollapsed(!root.classList.contains('easyread-all-collapsed'), true);
    });

    applyItemOrder(Array.prototype.slice.call(root.querySelectorAll('.easyread-item')));
    var form = root.closest('form');
    if (form) {
      if (!form.dataset.easyreadBound) {
        form.dataset.easyreadBound = '1';

        // Normal Submit.
        form.addEventListener('submit',function(e){
          if (form._easyReadPendingUploads > 0) {
            e.preventDefault();
            return false;
          }
          prepareForm(form);
        },true);

        // Synchronise our JSON before CMSMS's own delegated Apply/Submit
        // handlers run.  Do not prevent, replace or resubmit those controls:
        // Content Manager owns the page-lock lifecycle.
        form.addEventListener('click',function(e){
          var el = e.target && e.target.closest ? e.target.closest('button,input') : null;
          if (!el || !form.contains(el)) return;
          var type = String(el.getAttribute('type') || '').toLowerCase();
          if (el.tagName === 'BUTTON' || type === 'submit' || type === 'button' || type === 'image') {
            prepareForm(form);
          }
        },true);

        // Keep the JSON current before other CMSMS controls run.
        var prepareFromControl = function(e){
          var el = e.target && e.target.closest ? e.target.closest('button,input') : null;
          if (!el || !form.contains(el)) return;
          var type = String(el.getAttribute('type') || '').toLowerCase();
          if (el.tagName === 'BUTTON' || type === 'submit' || type === 'button' || type === 'image') {
            prepareForm(form);
          }
        };
        form.addEventListener('pointerdown',prepareFromControl,true);
        form.addEventListener('mousedown',prepareFromControl,true);
      }
    }

    root.querySelectorAll('.easyread-item').forEach(function(item){bindItem(root,item);updateImageControls(item);});
    var add = root.querySelector('.easyread-add');
    if (add) add.addEventListener('click',function(){addItem(root);});

    var list = root.querySelector('.easyread-items');
    if (list) list.addEventListener('dragover',function(e){
      var dragging = root.querySelector('.easyread-dragging');
      if (!dragging) return;
      e.preventDefault();
      var target = e.target.closest('.easyread-item');
      if (!target || target === dragging || target.parentNode !== list) return;
      root.querySelectorAll('.easyread-item').forEach(function(x){x.classList.remove('easyread-drop-before','easyread-drop-after');});
      var rect = target.getBoundingClientRect();
      var after = e.clientY > rect.top + rect.height/2;
      target.classList.add(after ? 'easyread-drop-after' : 'easyread-drop-before');
      root._easyReadDropTarget = target;
      root._easyReadDropAfter = after;
    });

    if (list) list.addEventListener('drop',function(e){
      var dragging = root.querySelector('.easyread-dragging');
      var target = root._easyReadDropTarget;
      if (!dragging || !target) return;
      e.preventDefault();
      moveItem(root,dragging,target,!!root._easyReadDropAfter);
      root._easyReadDropTarget = null;
      root._easyReadDropAfter = false;
      root.querySelectorAll('.easyread-item').forEach(function(x){x.classList.remove('easyread-drop-before','easyread-drop-after');});
    });

    sync(root);
  }

  function init(){ document.querySelectorAll('.easyread-editor').forEach(bindRoot); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded',init);
  else init();
})();
</script>
HTML;
    }

    private function baseKey($blockName)
    {
        return substr(sha1((string)$blockName), 0, 12);
    }

    private function fieldName($base, $type, $id)
    {
        return 'easyread_'.$type.'_'.$base.'_'.$this->cleanItemId($id);
    }

    private function newBlankItem()
    {
        return array('id' => $this->makeItemId(), 'image' => '', 'alt' => '', 'content' => '');
    }

    private function makeItemId()
    {
        try {
            $rand = bin2hex(random_bytes(5));
        } catch (Exception $e) {
            $rand = str_replace('.', '', uniqid('', true));
        }
        return 'er_'.dechex(time()).'_'.$rand;
    }

    private function cleanItemId($id)
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '', (string)$id);
    }

    private function cleanStoredImage($image)
    {
        $image = str_replace('\\', '/', trim((string)$image));
        $image = ltrim($image, '/');
        if (strpos($image, '..') !== false) return '';
        if ($image === '') return '';
        if (strpos($image, self::UPLOAD_SUBDIR.'/') !== 0) return '';
        return preg_replace('#[^A-Za-z0-9._/\-]#', '', $image);
    }

    private function uploadDirectory()
    {
        $config = cms_config::get_instance();
        return cms_join_path($config['uploads_path'], self::UPLOAD_SUBDIR);
    }

    private function ensureUploadDirectory()
    {
        $dir = $this->uploadDirectory();
        if (is_dir($dir)) return is_writable($dir);
        return @mkdir($dir, 0775, true) && is_writable($dir);
    }

    public function handleImageUpload($file)
    {
        if (!isset($file['tmp_name'], $file['name'], $file['size'], $file['error'])) {
            return array('error' => 'Easy Read: the picture upload was not valid.');
        }

        $error = (int)$file['error'];
        if ($error !== UPLOAD_ERR_OK) {
            return array('error' => 'Easy Read: the picture could not be uploaded (upload error '.$error.').');
        }

        if ((int)$file['size'] <= 0 || (int)$file['size'] > self::MAX_UPLOAD_BYTES) {
            return array('error' => 'Easy Read: pictures must be smaller than 8 MB.');
        }

        $tmp = (string)$file['tmp_name'];
        if (!is_uploaded_file($tmp)) {
            return array('error' => 'Easy Read: the uploaded picture could not be verified.');
        }

        $info = @getimagesize($tmp);
        if ($info === false || empty($info['mime'])) {
            return array('error' => 'Easy Read: please upload a real JPG, PNG, GIF or WebP picture.');
        }

        $mimeMap = array(
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        );
        $mime = strtolower((string)$info['mime']);
        if (!isset($mimeMap[$mime])) {
            return array('error' => 'Easy Read: only JPG, PNG, GIF and WebP pictures are allowed.');
        }

        if (!$this->ensureUploadDirectory()) {
            return array('error' => 'Easy Read: CMSMS cannot write to uploads/'.self::UPLOAD_SUBDIR.'. Please check the folder permissions.');
        }

        $original = pathinfo((string)$file['name'], PATHINFO_FILENAME);
        $base = preg_replace('/[^A-Za-z0-9_-]+/', '-', $original);
        $base = trim($base, '-_');
        if ($base === '') $base = 'easy-read-image';
        $base = substr($base, 0, 80);

        try {
            $suffix = bin2hex(random_bytes(4));
        } catch (Exception $e) {
            $suffix = substr(md5(uniqid('', true)), 0, 8);
        }

        $filename = $base.'-'.$suffix.'.'.$mimeMap[$mime];
        $destination = cms_join_path($this->uploadDirectory(), $filename);

        if (!@move_uploaded_file($tmp, $destination)) {
            return array('error' => 'Easy Read: the picture was valid but could not be saved.');
        }

        @chmod($destination, 0664);
        return array('image' => self::UPLOAD_SUBDIR.'/'.$filename);
    }

    public function storedImageUrl($image)
    {
        $config = cms_config::get_instance();
        return rtrim($config['uploads_url'], '/').'/'.$this->urlPath($image);
    }

    private function urlPath($path)
    {
        $parts = explode('/', str_replace('\\', '/', $path));
        return implode('/', array_map('rawurlencode', $parts));
    }
}
