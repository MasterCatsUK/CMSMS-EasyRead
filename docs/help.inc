<h3>Easy Read guide</h3>
<p>Easy Read helps you present information using short, clear text and a meaningful picture together. Each module item contains one picture, an Alt Description and WYSIWYG content.</p>

<h3>Using Easy Read in the page editor</h3>
<ol>
  <li>Select <strong>+ Add Item</strong> for each new idea.</li>
  <li>Use the round add/edit picture button at the top-left of the picture area.</li>
  <li>Write an Alt Description that briefly explains the important meaning of the picture.</li>
  <li>Write the matching Easy Read text in the Content editor.</li>
  <li>Wait for the “Picture uploaded” message, then use the normal CMSMS <strong>Apply</strong> or <strong>Submit</strong> button.</li>
  <li>Use Move up, Move down or the drag handle to change the order. Use the picture trash button to remove only the picture, or Delete Item to remove the whole item.</li>
</ol>

<h3>How to write Easy Read text</h3>
<ul>
  <li>Put one main idea in each item and match it with one useful picture.</li>
  <li>Put the most important information first.</li>
  <li>Use short sentences and familiar, everyday words.</li>
  <li>Write directly to the reader using words such as “you” and “we”.</li>
  <li>Use active sentences. For example, “We will send you a letter” is clearer than “A letter will be sent”.</li>
  <li>Explain difficult words, initials and abbreviations when they must be used.</li>
  <li>Be specific about who will do something, what will happen and when it will happen.</li>
  <li>Use clear headings and bullet lists to break up longer information.</li>
  <li>Avoid long paragraphs, jargon, figures of speech and unnecessary detail.</li>
  <li>Read the text aloud and, whenever possible, ask people with learning disabilities to review it before publishing.</li>
</ul>

<p><strong>Example:</strong> Instead of “Attendees are requested to present themselves at reception prior to the scheduled appointment”, write “Come to reception before your appointment.”</p>

<h3>Choosing pictures</h3>
<ul>
  <li>Choose a picture that explains the same idea as the text beside it.</li>
  <li>Use clear pictures with one obvious subject and a simple, uncluttered background.</li>
  <li>Use real people, places or objects when that will help the reader recognise them.</li>
  <li>Keep the picture style and meaning consistent throughout the page.</li>
  <li>Use respectful, age-appropriate and inclusive pictures.</li>
  <li>Avoid decorative, confusing or very detailed pictures that do not help explain the message.</li>
  <li>Make sure you have permission or a suitable licence to publish every picture.</li>
</ul>

<p><strong>Alt Description example:</strong> “A receptionist showing a visitor where to wait.” Avoid file names or descriptions such as “image123.jpg”.</p>

<h3>Optional paid picture library</h3>
<p><a href="https://www.photosymbols.com/" target="_blank" rel="noopener noreferrer">PhotoSymbols</a> is a paid subscription service with a library of images designed for Easy Read information. It can be useful when you need clear, consistent pictures. Check its current subscription and licence terms before using or publishing its images. Easy Read does not require PhotoSymbols and is not affiliated with it.</p>

<h3>Technical setup: add Easy Read to a page template</h3>
<pre>{content_module module='EasyRead' block='easy_read' label='Easy Read Summary' assign='easyread_data'}</pre>

<p>The value stored by the page is JSON. To turn it into an array for your own Smarty/Bootstrap markup, add:</p>
<pre>{EasyRead data=$easyread_data to='easyread'}</pre>

<p>You can then use:</p>
<pre>{foreach $easyread as $item}
  {if $item.image_url}
    &lt;img src="{$item.image_url|escape}" alt="{$item.alt|escape}"&gt;
  {/if}
  {$item.content}
{/foreach}</pre>

<h3>Built-in simple output</h3>
<pre>{content_module module='EasyRead' block='easy_read' label='Easy Read Summary' assign='easyread_data'}
{EasyRead data=$easyread_data}</pre>

<p>The built-in output uses Bootstrap 5 layout classes and neutral easyread-summary CSS classes. Include Bootstrap in your site theme, or edit the Design Manager template to match your own stylesheet.</p>

<h3>Pictures</h3>
<p>Pictures are uploaded to <code>uploads/EasyRead</code> as soon as they are selected. JPG, PNG, GIF and WebP files up to 8 MB are accepted. Wait for the “Picture uploaded” message before pressing Apply or Submit. Those buttons are disabled while the upload is running.</p>

<p>Use the add/change icon at the top-left of the picture to choose a file. Use the trash icon at the bottom-right to remove the picture from the item. On narrow screens the picture, Alt Description and Content fields stack vertically.</p>

<p>Move up, Move down and drag-and-drop change the logical display order without detaching the live WYSIWYG editor, so TinyMCE remains editable. Newly added items use the same CMSMS WYSIWYG configuration as existing items.</p>

<p>Apply uses CMSMS Content Manager's normal AJAX save and lock handling. Submit uses CMSMS's normal full-page save. Uninstalling Easy Read removes its saved page-block values and the <code>uploads/EasyRead</code> folder.</p>

<h3>Design Manager templates</h3><p>Edit Easy Read Summary under Layout → Design Manager. Render with {EasyRead data=$easyread_data}; choose a named template with template='Easy Read Summary'. The to= parameter continues to return data only.</p>

<h3>External image links and privacy</h3>
<p>Images are stored under uploads/EasyRead until removed by the site administrator or module uninstall. Remove unnecessary personal information from pictures and image metadata before uploading.</p>
<p>Use image link sends an HTTP(S) GET request from the hosting server to the public image URL entered by an editor. The chosen host receives the URL, ordinary HTTP request headers and the server IP address. Requests occur only when importing a link; the returned image is stored locally. No fixed external service is used: consult the Terms of Service and Privacy Policy published by the selected image host. Redirects and private network addresses are rejected.</p>
<p>The admin editor uses an easyreadEditor_PAGEID_BLOCK cookie containing only the shown/hidden editing preference. It is scoped to the CMSMS admin directory, lasts up to one year, uses SameSite=Lax and is Secure on HTTPS. Clear the cookie to reset the preference. No frontend visitor cookie or analytics script is installed by this module.</p>
<p>Suggested site privacy wording: “Our content editors can upload images or import them from a public image link. Imported images are stored on our hosting; the selected image host receives a normal server request. The editor also uses a cookie to remember whether its editing controls are shown or hidden.”</p>
<h3>Licence</h3><p>GPL-2.0-or-later; see docs/LICENSE in the module folder.</p>
