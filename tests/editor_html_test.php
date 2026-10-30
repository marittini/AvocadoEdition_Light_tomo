<?php
// Isolated sanitizer regression; no installation or credentials required.
define('_GNUBOARD_', true);
define('G5_PLUGIN_PATH', __DIR__.'/../AvocadoEdition_Light/plugin');
$cache=sys_get_temp_dir().'/tomo-html-'.bin2hex(random_bytes(6));mkdir($cache);mkdir($cache.'/cache');define('G5_DATA_PATH',$cache);
$_SERVER['HTTP_HOST']='example.test';require __DIR__.'/../AvocadoEdition_Light/lib/common.lib.php';
function verify($condition,$message){if(!$condition)throw new RuntimeException($message);echo "PASS $message\n";}
$source='<details open><summary>캐릭터 정보</summary><div data-type="detailsContent"><p>접힌 본문</p></div></details><ul data-type="taskList"><li data-type="taskItem" data-checked="true"><label><input type="checkbox" checked></label><div><p>완료한 항목</p></div></li></ul><div data-youtube-video=""><iframe src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ" width="640" height="360"></iframe></div><p><span style="background-color:#ffcc00;line-height:1.8">서식</span><sup>위</sup><sub>아래</sub></p><table><tr><th style="background-color:#fcf0f3">제목</th><td>내용</td></tr></table>';
$result=html_purifier($source);if(getenv('DEBUG_EDITOR_HTML'))echo $result,"\n";
verify(str_contains($result,'<details')&&str_contains($result,'<summary>')&&str_contains($result,'detailsContent'),'collapsible profile blocks survive the sanitizer');
verify(str_contains($result,'taskList')&&str_contains($result,'taskItem')&&str_contains($result,'data-checked="true"')&&str_contains($result,'type="checkbox"'),'checklists survive the sanitizer');
verify(str_contains($result,'youtube-nocookie.com/embed/')&&str_contains($result,'<iframe'),'approved YouTube embed survives');
verify(str_contains($result,'background-color')&&str_contains($result,'<sup>')&&str_contains($result,'<sub>')&&str_contains($result,'<table>'),'highlight, superscript/subscript and tables survive');
$unsafe=html_purifier('<script>alert(1)</script><img src="x" onerror="alert(1)"><a href="javascript:alert(1)">bad</a><iframe src="https://evil.example/embed/x"></iframe><input type="password" name="secret">');
verify(!str_contains($unsafe,'<script')&&!str_contains($unsafe,'onerror')&&!str_contains($unsafe,'javascript:')&&!str_contains($unsafe,'evil.example')&&!str_contains($unsafe,'<input'),'unsafe scripts, event handlers, links and iframe hosts remain blocked');
$annotations=html_purifier('<p><ruby><span class="tomo-ruby-base">漢字</span><rp>(</rp><rt>かんじ</rt><rp>)</rp></ruby><span class="tomo-spoiler">비밀</span><span style="letter-spacing:1.5px">자간</span></p>');
verify(str_contains($annotations,'<ruby>')&&str_contains($annotations,'<rt>かんじ</rt>')&&str_contains($annotations,'<rp>'),'ruby annotations survive server HTML filtering');
verify(str_contains($annotations,'class="tomo-spoiler"')&&str_contains($annotations,'letter-spacing:1.5px'),'spoiler class and letter spacing survive without inline scripts');
$badRuby=html_purifier('<ruby>base<rt><img src="x" onerror="alert(1)"><script>alert(1)</script></rt></ruby>');
verify(!str_contains($badRuby,'<script')&&!str_contains($badRuby,'onerror'),'annotation elements do not bypass XSS filtering');
