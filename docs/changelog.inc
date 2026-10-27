<h3>0.1.24</h3>
<ul><li>Added the GPL-2.0-or-later licence and PHP licence headers.</li><li>Made the help-page permission check explicit and added dependency metadata and an uninstall warning.</li><li>Documented external image imports, image storage and the admin editor preference cookie.</li></ul>
<h3>0.1.23</h3>
<ul><li>General-purpose public distribution with neutral frontend CSS classes and setup examples.</li><li>Existing Design Manager templates remain unchanged during upgrade; copy the new factory markup manually when required.</li></ul>
<h3>0.1.20</h3><p>Adding another item now opens a WYSIWYG editor, using the same TinyMCE configuration and GPL license key as the editors already on the page.</p>
<h3>0.1.19</h3><p>Add +/− to Use image link and support dropping a single image file onto the picture frame, preserving the Add picture control and existing validation.</p>
<h3>0.1.18</h3><p>Add Upload image and Use image link choices. Validate and copy public JPG, PNG, GIF and WebP images into CMSMS, with an 8 MB limit.</p>
<h3>0.1.17</h3><p>Align the editor Show/Hide button to the left.</p>
<h3>0.1.16</h3><p>Place Show/Hide above the information box and collapse the guidance along with the editor fields. Keep existing per-page cookie preferences.</p>
<h3>0.1.15</h3><p>Remember the editor Show/Hide state per saved page and block using an admin-path cookie. New pages default to expanded. Published content is unaffected.</p>
<h3>0.1.14</h3><p>Use only the information class for editor guidance. Add Photosymbols and advice on pictures explaining the accompanying text.</p>
<h3>0.1.13</h3><p>Wrap editor guidance in the CMS information div.</p>
<h3>0.1.12</h3><p>Page editor guidance for Easy Read writing and pictures, plus an accessible Show/Hide Easy Read control matching PageSections. Hiding the editor does not disable or delete saved content.</p>
<h3>0.1.11</h3><p>Editable EasyRead::summary templates in Design Manager; optional template parameter; compatible data-only mode.</p>
<h3>0.1.10</h3>
<ul>
  <li>Added a read-only Easy Read guide under Extensions &gt; Easy Read.</li>
  <li>Added page-editor and template setup instructions.</li>
  <li>Added practical guidance for writing Easy Read text, choosing pictures and writing Alt Descriptions.</li>
  <li>Added an optional, clearly labelled paid PhotoSymbols recommendation and licensing reminder.</li>
</ul>

<h3>0.1.9</h3>
<ul>
  <li>Fixed the moved item's WYSIWYG remaining as a plain textarea after reordering.</li>
  <li>Reordering no longer detaches or relocates live TinyMCE editor nodes.</li>
  <li>Cards use a logical/CSS order, and saved JSON follows that same order.</li>
  <li>New rows use TinyMCE 4's native <code>mceAddEditor</code> command and the MicroTiny configuration already loaded by CMSMS.</li>
  <li>Retains selector-based TinyMCE initialization for new rows as a compatibility fallback.</li>
</ul>

<h3>0.1.8</h3>
<ul>
  <li>Fixed WYSIWYG editors becoming unclickable after Move up, Move down or drag-and-drop reordering.</li>
  <li>Editor content and configuration are preserved while an item moves, then TinyMCE is safely reinitialized.</li>
  <li>Drag-and-drop now changes the DOM order only once on drop instead of repeatedly moving a live editor during dragover.</li>
  <li>New items now inherit the active CMSMS WYSIWYG classes and TinyMCE configuration instead of remaining plain textareas.</li>
</ul>

<h3>0.1.7</h3>
<ul>
  <li>Changed each backend item to a responsive two-column layout.</li>
  <li>Moved Alt Description and WYSIWYG Content to the right of the picture on larger screens.</li>
  <li>Added a compact add/change-picture control over the top-left of the picture.</li>
  <li>Added a trash control over the bottom-right of the picture.</li>
  <li>Items stack vertically for easier editing on phones and narrow screens.</li>
  <li>Kept the asynchronous upload and native CMSMS Apply/locking behaviour from 0.1.6 unchanged.</li>
</ul>

<h3>0.1.6</h3>
<ul>
  <li>Pictures now upload asynchronously as soon as they are selected.</li>
  <li>Only the resulting server-side <code>EasyRead/filename.ext</code> path is stored in the hidden page JSON.</li>
  <li>Removed the forced full multipart Apply submission that conflicted with CMSMS content locks.</li>
  <li>CMSMS native AJAX Apply and its lock lifecycle are no longer intercepted or replaced.</li>
  <li>Apply and Submit are briefly disabled while an image upload is in progress.</li>
</ul>

<h3>0.1.5</h3>
<ul>
  <li>Removed the base64/data-URL image fallback from the hidden page JSON.</li>
  <li>Fixed HTTP 413 Request Entity Too Large errors when saving pages with newly selected pictures.</li>
  <li>Image previews now use temporary browser object URLs instead of embedding image bytes in the form.</li>
  <li>Apply still switches to a normal multipart submission when a new picture is selected so the real file is uploaded.</li>
  <li>Suppresses the misleading browser leave-page warning during that intentional Apply submission.</li>
</ul>

<h3>0.1.4</h3>
<ul>
  <li>Fixed new pictures being lost when the CMSMS Apply button uses AJAX/form serialization.</li>
  <li>Apply now uses a normal multipart form submission whenever an Easy Read file input contains a newly selected picture.</li>
  <li>The clicked Apply button name/value is preserved so CMSMS still treats the request as Apply rather than Submit.</li>
  <li>Normal CMSMS Apply behaviour is unchanged when no new Easy Read picture is waiting to upload.</li>
</ul>

<h3>0.1.3</h3>
<ul>
  <li>Fixed picture uploads being lost when CMSMS Content Manager saves a page using JavaScript/AJAX.</li>
  <li>Added a validated data-URL fallback so Apply and Submit can both save new pictures.</li>
  <li>The fallback data is temporary; only the final <code>EasyRead/filename.ext</code> path is stored in the page JSON.</li>
</ul>

<h3>0.1.2</h3>
<ul>
  <li>Uninstall now removes saved Easy Read page-block data.</li>
  <li>Uninstall now removes all files from <code>uploads/EasyRead</code> and removes the directory.</li>
  <li>Uninstall removes module preferences and the Smarty plugin registration.</li>
</ul>

<h3>0.1.1</h3>
<ul>
  <li>Fixed Easy Read data not being synchronised when Content Manager's Apply button is used.</li>
  <li>Made Delete Item resilient to WYSIWYG editor cleanup errors.</li>
  <li>Synchronises WYSIWYG changes continuously so Apply and Submit save the same data.</li>
  <li>Fixed <code>to=</code> array assignment to use the current Smarty template scope.</li>
</ul>

<h3>0.1.0</h3>
<ul>
  <li>Initial release.</li>
  <li>Repeatable Easy Read page blocks with picture upload, alt description and WYSIWYG content.</li>
  <li>Drag-and-drop and Move Up/Move Down ordering.</li>
</ul>
<h3>0.1.21</h3><p>Render CMSMS Smarty and module tags stored in Easy Read content before passing it to frontend templates, removing the need for <code>{eval}</code> in Design Manager templates.</p>
<h3>0.1.22</h3><p>Add a permission that controls whether Easy Read appears in the Extensions menu, without affecting page-editor content blocks.</p>
