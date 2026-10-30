<?php
define('_GNUBOARD_', true);
require __DIR__.'/../AvocadoEdition_Light/lib/tomo_editor.lib.php';
function expect_empty_value($input,$expected,$label){if(tomo_normalize_editor_html($input)!==$expected)throw new RuntimeException($label);echo "PASS $label\n";}
foreach (['', ' ', "\n\t", '<p></p>', '<p><br></p>', '<p><br></p><p></p>', '<div><span>&nbsp;&#160;&#x200B;</span></div>', '<p>&#8203;&#65279;</p>', '<blockquote><p><em> </em></p></blockquote>', '<p style="font-size:16px;line-height:1.8"><br></p>', '<!-- empty -->'] as $empty) expect_empty_value($empty,'','empty text/markup becomes an empty string');
foreach (['0','<p>공지입니다.</p>','<p>&lt;p&gt;</p>','<p>&amp;nbsp;</p>','<img src="a.png" alt="">','<iframe src="https://www.youtube.com/embed/x"></iframe>','<hr>','<table><tr><td></td></tr></table>','<details><summary></summary><div data-type="detailsContent"></div></details>','<div class="visual-widget"></div>','<div style="height:100px;background-image:url(a.png)"></div>','<input type="checkbox" checked>'] as $content) expect_empty_value($content,$content,'text/media/layout is preserved byte-for-byte');
expect_empty_value("\xff","\xff",'invalid UTF-8 is preserved');
