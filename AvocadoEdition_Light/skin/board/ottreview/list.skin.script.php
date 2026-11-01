<?php 
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

function markup_content($str)
{

	$source[] = "/(?<!\*)\|\|(?![\s*])(.*?)(?<![\s*])\|\|(?!\*)/s";
	$target[] = "<a class='blur-txt' title='스포일러' alt='스포일러'>$1</a>";
	$source[] = "/(?<!\*)\*\*(?![\s*])(.*?)(?<![\s*])\*\*(?!\*)/s";
	$target[] = "<strong>$1</strong>";
	$source[] = "/(?<!\*)\*(?![\s*])(.*?)(?<![\s*])\*(?!\*)/s";
	$target[] = "<span class='italictext'>$1</span>";
	$source[] = "/(?<!\*)\*\*\*(?![\s*])(.*?)(?<![\s*])\*\*\*(?!\*)/s";
	$target[] = "<span class='italictext'><strong>$1</strong></span>";
	$source[] = "/(?<!\*)\`\`(?![\s*])(.*?)(?<![\s*])\`\`(?!\*)/s";
	$target[] = "<span class='txt-box'>$1</span>";

return preg_replace($source, $target, $str);
}

function textggu_change($str)
{
	$str = preg_replace('`제목1\[(?![\s*])(.*?)(?<![\s*])\]`', '<span class="textggu--title1">$1</span>', $str);
    $str = preg_replace('`제목2\[(?![\s*])(.*?)(?<![\s*])\]`', '<span class="textggu--title2">$1</span>', $str);
    $str = preg_replace('`제목3\[(?![\s*])(.*?)(?<![\s*])\]`', '<span class="textggu--title3">$1</span>', $str);
    $str = preg_replace('`제목4\[(?![\s*])(.*?)(?<![\s*])\]`', '<span class="textggu--title4">$1</span>', $str);
    $str = preg_replace('`제목5\[(?![\s*])(.*?)(?<![\s*])\]`', '<span class="textggu--title5">$1</span>', $str);
    $str = preg_replace('`제목6\[(?![\s*])(.*?)(?<![\s*])\]-(?![\s*])(.*?)(?<![\s*])-`', '<span class="textggu--title6" data-text="$2">$1</span>', $str);
    $str = preg_replace('`제목7\[(?![\s*])(.*?)(?<![\s*])\]`', '<span class="textggu--title7">$1</span>', $str);

    $str = preg_replace('`소제1\[(?![\s*])(.*?)(?<![\s*])\]`', '<span class="textggu--sub1">$1</span>', $str);
    $str = preg_replace('`소제2\[(?![\s*])(.*?)(?<![\s*])\]`', '<span class="textggu--sub2">$1</span>', $str);
    $str = preg_replace('`소제3\[(?![\s*])(.*?)(?<![\s*])\]`', '<span class="textggu--sub3">$1</span>', $str);
    $str = preg_replace('`소제4\[(?![\s*])(.*?)(?<![\s*])\]`', '<span class="textggu--sub4">$1</span>', $str);
    $str = preg_replace('`소제5\[(?![\s*])(.*?)(?<![\s*])\]`', '<span class="textggu--sub5">$1</span>', $str);

    $str = preg_replace('`기타1\[(?![\s*])(.*?)(?<![\s*])\]`', '<span class="textggu--etc1">$1</span>', $str);
    $str = preg_replace('`기타2\[(?![\s*])(.*?)(?<![\s*])\]`', '<span class="textggu--etc2">$1</span>', $str);
    $str = preg_replace('`기타3\[(?![\s*])(.*?)(?<![\s*])\]`', '<span class="textggu--etc3">$1</span>', $str);
    $str = preg_replace('`기타4\[(?![\s*])(.*?)(?<![\s*])\]`', '<span class="textggu--etc4">$1</span>', $str);
    $str = preg_replace('`기타5\[(?![\s*])(.*?)(?<![\s*])\]`', '<span class="textggu--etc5">$1</span>', $str);
    $str = preg_replace('`기타6\[(?![\s*])(.*?)(?<![\s*])\]`', '<span class="textggu--etc6">$1</span>', $str);

	return $str;
}

function convertYouTubeLinks($content, $autoplay = false) {
    $pattern = '/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/i';
    return preg_replace_callback($pattern, function($m) use ($autoplay) {
        $vid = $m[1];
        $uid = 'ott_yt_'.$vid.'_'.substr(md5(uniqid((string)mt_rand(), true)), 0, 6);
        $params = 'enablejsapi=1&rel=0&playsinline=1';
        if ($autoplay) {
            $params .= '&autoplay=1&mute=1';
        }
        $src = 'https://www.youtube.com/embed/'.$vid.'?'.$params;
        $wrap_class = 'ott_yt_wrap'.($autoplay ? ' ott_yt_autoplay' : '');

        $out  = '<div class="'.$wrap_class.'" data-yt-frame="'.$uid.'">';
        $out .= '<iframe id="'.$uid.'" src="'.$src.'" frameborder="0" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>';
        if ($autoplay) {
            $out .= '<button type="button" class="ott_yt_mute_toggle" data-target="'.$uid.'" title="소리 켜기/끄기"><i class="fa-solid fa-volume-xmark"></i></button>';
        }
        $out .= '</div>';
        return $out;
    }, $content);
}

/**
 * X(구 트위터) 게시물 URL을 공식 임베드용 blockquote로 변환합니다.
 * - x.com / twitter.com
 * - www / mobile 서브도메인
 * - /photo/1, 쿼리스트링이 붙은 공유 URL
 * 를 모두 지원합니다.
 */
function ott_x_embed_markup($username, $status_id)
{
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$username);
    $status_id = preg_replace('/[^0-9]/', '', (string)$status_id);

    if (!$username || !$status_id) {
        return '';
    }

    $url = 'https://x.com/'.$username.'/status/'.$status_id;
    $safe_url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

    $out  = '<div class="ott_x_embed">';
    $out .= '<blockquote class="twitter-tweet" data-dnt="true">';
    $out .= '<a href="'.$safe_url.'" target="_blank" rel="noopener noreferrer">X에서 게시물 보기</a>';
    $out .= '</blockquote>';
    $out .= '</div>';

    return $out;
}

function convertXLinks($content)
{
    if ($content === null || $content === '') {
        return $content;
    }

    $content = (string)$content;
    $embeds = array();
    $protected_tags = array();

    $make_embed_token = function($username, $status_id) use (&$embeds) {
        $token = "\x1AOTT_X_EMBED_".count($embeds)."\x1A";
        $embeds[$token] = ott_x_embed_markup($username, $status_id);
        return $token;
    };

    // conv_content()나 기존 자동 링크 처리로 이미 <a>가 된 URL도 통째로 임베드로 교체합니다.
    $anchor_pattern = '~<a\\b[^>]*\\bhref=(["\\\'])https?://(?:www\\.|mobile\\.)?(?:x\\.com|twitter\\.com)/([a-zA-Z0-9_]{1,15})/status/(\\d+)(?:[/?#][^"\\\'<>]*)?\\1[^>]*>.*?</a>~is';
    $content = preg_replace_callback($anchor_pattern, function($m) use ($make_embed_token) {
        return $make_embed_token($m[2], $m[3]);
    }, $content);

    // 남아 있는 HTML 태그의 src/href 속성 내부 URL은 건드리지 않도록 잠시 보호합니다.
    $content = preg_replace_callback('/<[^>]+>/s', function($m) use (&$protected_tags) {
        $token = "\x1AOTT_HTML_TAG_".count($protected_tags)."\x1A";
        $protected_tags[$token] = $m[0];
        return $token;
    }, $content);

    // 일반 텍스트로 붙여넣은 X/트위터 게시물 URL 변환
    $raw_pattern = '~https?://(?:www\\.|mobile\\.)?(?:x\\.com|twitter\\.com)/([a-zA-Z0-9_]{1,15})/status/(\\d+)(?:[/?#][^\\s<>"\\\']*)?~i';
    $content = preg_replace_callback($raw_pattern, function($m) use ($make_embed_token) {
        return $make_embed_token($m[1], $m[2]);
    }, $content);

    if ($protected_tags) {
        $content = strtr($content, $protected_tags);
    }
    if ($embeds) {
        $content = strtr($content, $embeds);
    }

    return $content;
}

function url_auto_link2($str)
{
	global $g5;
	global $config;

	$str = str_replace(array("&lt;", "&gt;", "&nbsp;"), array("\t_lt_\t", "\t_gt_\t", "\t_nbsp_\t"), $str);
	$str = preg_replace("/([^(href=\"?'?)|(src=\"?'?)]|\(|^)((http|https|ftp|telnet|news|mms):\/\/[a-zA-Z0-9\.-]+\.[가-힣\xA1-\xFEa-zA-Z0-9\.:&#=_\?\/~\+%@;\-\|\,\(\)]+)/i", "\\1<A HREF=\"\\2\" TARGET=\"{$config['cf_link_target']}\">\\2</A>", $str);
	$str = preg_replace("/(^|[\"'\s(])(www\.[^\"'\s()]+)/i", "\\1<A HREF=\"http://\\2\" TARGET=\"{$config['cf_link_target']}\">\\2</A>", $str);
	$str = preg_replace("/[0-9a-z_-]+@[a-z0-9._-]{4,}/i", "<a href=\"mailto:\\0\">\\0</a>", $str);
	$str = str_replace(array("\t_nbsp_\t", "\t_lt_\t", "\t_gt_\t"), array("&nbsp;", "&lt;", "&gt;"), $str);

	return $str;
}
?>
