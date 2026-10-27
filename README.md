# EasyRead 0.1.23 — Design Manager templates

1. Back up the existing modules/EasyRead folder and CMS database.
2. Extract EasyRead-0.1.23.zip and replace the site's modules/EasyRead folder with the included EasyRead folder.
3. In Extensions → Modules, run Upgrade for EasyRead. Do not uninstall first.
4. Open Layout → Design Manager → Templates. Look for Easy Read Summary (type EasyRead::summary).
5. Add the generic integration below to your CMSMS page template. Replace any existing Easy Read row loop with `{EasyRead data=$easyread_data}`.

Design Manager controls the Easy Read heading, pictures and text layout. The included template uses Bootstrap 5 layout classes and neutral `easyread-summary` CSS classes. Supply Bootstrap in your site theme, or edit the Design Manager template to use your own layout. Any optional visitor toggle and preference cookie belong to your page template.

Choose a named template with `{EasyRead data=$easyread_data template='Easy Read Summary'}`. Without template=, the default EasyRead::summary template is used. Existing `{EasyRead data=$easyread_data to='easyread'}` calls still return data without rendering HTML.

Repeat upgrades preserve existing templates and user edits. Uninstall retains Design Manager templates, like the existing module's retained content/uploads. No page data migration is required.

## Generic page-template integration

Register the content block once in the template body, before rendering it:

```smarty
{content_module module='EasyRead' block='easy_read'
  label='Easy Read Summary' assign='easyread_data'}
{EasyRead data=$easyread_data}
```

For custom output, use `{EasyRead data=$easyread_data to='easyread'}` and loop
over `$easyread`. The `to` call returns data without output. Do not register
the same content block twice. Editors then add pictures, alt descriptions and
short text under Easy Read Summary in the page editor.

## Public distribution (0.1.23)

The bundled frontend template and setup examples use neutral names. Existing
Design Manager templates are preserved on upgrade. To use the new factory
markup on an existing installation, update your Easy Read Summary template
from `templates/default.tpl` after backing up any custom changes. The bundled
template uses `easyread-summary`, `easyread-summary-item`,
`easyread-summary-image` and `easyread-summary-text` as its CSS hooks. Update
your site stylesheet to match these hooks if needed.

This distribution has been syntax checked locally; installation in a running
CMSMS site remains to be verified.

## Page editor
Guidance appears under the Easy Read heading. The Show/Hide Easy Read button sits above the guidance. Hide Easy Read collapses the guidance, item fields and Add Item button; Show Easy Read restores them. This only changes the editor display, not published content or saved data. The editor remembers Show/Hide separately for each saved page and Easy Read block, using a one-year cookie in this browser. New unsaved pages start expanded.

## Image choices
Each item offers Upload image or Use image link. Paste a direct public HTTP(S) image URL and click Import image. The downloaded bytes are checked as JPG, PNG, GIF or WebP, up to 8 MB, and saved into uploads/EasyRead. Apply or Submit saves the selected picture to the page. PHP cURL is required. Redirect links are rejected with instructions to use the final image URL. Failed imports retain the previous picture.

Use image link + expands the link field; Use image link − closes it. Drop one image file from your computer onto the picture frame to upload it. Add picture + and the existing upload option remain available. The same file validation and 8 MB limit apply. Apply or Submit saves the page.

## Module tags in Easy Read content
Easy Read content now renders CMSMS Smarty and module tags automatically. For example, write `{YouTubeCatalog}` in the WYSIWYG editor. In the Easy Read Design Manager template, keep the usual `{$easyItem.content}` output; do not add `{eval}`.

## Extensions visibility
The `View Easy Read in Extensions` permission controls whether a user sees Easy Read in the Extensions menu. Assign it only to users who need the module help page. Removing it hides the Extensions entry, but does not remove Easy Read fields from the page editor or affect published pages.
