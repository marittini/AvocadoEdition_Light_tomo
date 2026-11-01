<?php
include_once('./_common.php');
include_once(G5_PATH.'/head.sub.php');
include_once('../color.config.php');
?>
<link rel="stylesheet" href="style.css" type="text/css">
<link rel="stylesheet" href="textggu.css" type="text/css">

<style>
.tool-preview {
	cursor: pointer;
}

.emo-header {
	margin-bottom: 20px;
	text-align: center;
}

.emo-header h2 {
	margin: 0 0 6px;
	font-size: 18px;
	font-weight: 700;
	color: #222;
	font-family: 'S-CoreDream-3Light', sans-serif;
}

.emo-header p {
	margin: 0;
	font-size: 12px;
	line-height: 1.5;
	color: #888;
}
</style>

<div id="main_body">
	<div class="main_div">
		<div class="txt-wrapper">

			<header class="emo-header">
				<h2>텍스트 꾸미기</h2>
				<p>원하는 서식을 클릭하면 입력창에 추가됩니다.</p>
			</header>

			<div class="card">
				<span class="card-title">◇치환자</span>
				<span class="pre">*치환자 옆에 띄어쓰기 없이!</span>

				<div class="tool-grid">
					<div class="tool-item">
						<div class="tool-preview" data-template="*내용*">
							<span class="italictext">기울임</span>
						</div>
					</div>

					<div class="tool-item">
						<div class="tool-preview" data-template="**내용**">
							<span style="font-weight:bold;">굵게</span>
						</div>
					</div>

					<div class="tool-item">
						<div class="tool-preview" data-template="***내용***">
							<span class="italictext" style="font-weight:bold;">굵게+기울임</span>
						</div>
					</div>

					<div class="tool-item">
						<div class="tool-preview" data-template="``내용``">
							<span class="txt-box">박스</span>
						</div>
					</div>

					<div class="tool-item">
						<div class="tool-preview" data-template="||내용||">
							<a href="#none" class="blur-txt">블러</a>
						</div>
					</div>
				</div>
			</div>

			<div class="card">
				<span class="card-title">◇텍스트 서식: 제목</span>
				<span class="pre">*내용에 쓰고 싶은 말 적기</span>

				<div class="tool-grid">
					<div class="tool-item">
						<div class="tool-preview" data-template="제목1[내용]">
							<span class="textggu--title1">텍스트</span>
						</div>
					</div>

					<div class="tool-item">
						<div class="tool-preview" data-template="제목2[내용]">
							<span class="textggu--title2">텍스트</span>
						</div>
					</div>

					<div class="tool-item">
						<div class="tool-preview" data-template="제목3[내용]">
							<span class="textggu--title3">텍스트</span>
						</div>
					</div>

					<div class="tool-item">
						<div class="tool-preview" data-template="제목4[내용]">
							<span class="textggu--title4">텍스트</span>
						</div>
					</div>

					<div class="tool-item">
						<div class="tool-preview" data-template="제목5[내용]">
							<span class="textggu--title5">텍스트</span>
						</div>
					</div>

					<div class="tool-item">
						<div class="tool-preview tool-preview--title6" data-template="제목6[제목]-부제-">
							<span class="textggu--title6" data-text="부제">텍스트</span>
						</div>
					</div>

					<div class="tool-item">
						<div class="tool-preview" data-template="제목7[내용]">
							<span class="textggu--title7">텍스트</span>
						</div>
					</div>
				</div>
			</div>

			<div class="card">
				<span class="card-title">◇텍스트 서식: 소제</span>

				<div class="tool-grid">
					<div class="tool-item">
						<div class="tool-preview" data-template="소제1[내용]">
							<span class="textggu--sub1">텍스트</span>
						</div>
					</div>

					<div class="tool-item">
						<div class="tool-preview" data-template="소제2[내용]">
							<span class="textggu--sub2">텍스트</span>
						</div>
					</div>

					<div class="tool-item">
						<div class="tool-preview" data-template="소제3[내용]">
							<span class="textggu--sub3">텍스트</span>
						</div>
					</div>

					<div class="tool-item">
						<div class="tool-preview" data-template="소제4[내용]">
							<span class="textggu--sub4">텍스트</span>
						</div>
					</div>

					<div class="tool-item">
						<div class="tool-preview" data-template="소제5[내용]">
							<span class="textggu--sub5">텍스트</span>
						</div>
					</div>
				</div>
			</div>

			<div class="card">
				<span class="card-title">◇텍스트 서식: 기타</span>

				<div class="tool-grid">
					<div class="tool-item">
						<div class="tool-preview" data-template="기타1[내용]">
							<span class="textggu--etc1">텍스트</span>
						</div>
					</div>

					<div class="tool-item">
						<div class="tool-preview" data-template="기타2[내용]">
							<span class="textggu--etc2">텍스트</span>
						</div>
					</div>

					<div class="tool-item">
						<div class="tool-preview" data-template="기타3[내용]">
							<span class="textggu--etc3">텍스트</span>
						</div>
					</div>

					<div class="tool-item">
						<div class="tool-preview" data-template="기타4[내용]">
							<span class="textggu--etc4">텍스트</span>
						</div>
					</div>

					<div class="tool-item">
						<div class="tool-preview" data-template="기타5[내용]">
							<span class="textggu--etc5">텍스트</span>
						</div>
					</div>

					<div class="tool-item">
						<div class="tool-preview" data-template="기타6[내용]">
							<span class="textggu--etc6">텍스트</span>
						</div>
					</div>
				</div>
			</div>

		</div>
	</div>
</div>

<script>
(function () {
	const hostWindow = window.parent && window.parent !== window ? window.parent : window;
	const hostDocument = hostWindow.document;

	function isEditable(el) {
		if (!el || !el.matches) return false;

		if (el.matches('textarea')) return true;

		if (el.matches('input[type="text"], input:not([type]), input[type="search"], input[type="url"], input[type="tel"], input[type="email"], input[type="password"]')) {
			return true;
		}

		if (el.isContentEditable) return true;

		return false;
	}

	function isUsable(el) {
		if (!isEditable(el)) return false;
		if (el.disabled || el.readOnly) return false;
		if (!hostDocument.body.contains(el)) return false;
		return true;
	}

	function saveTarget(el) {
		if (!isUsable(el)) return;

		hostWindow.avoLastTarget = el;

		if (typeof el.selectionStart === 'number' && typeof el.selectionEnd === 'number') {
			hostWindow.avoLastSelection = {
				start: el.selectionStart,
				end: el.selectionEnd
			};
		} else {
			hostWindow.avoLastSelection = null;
		}
	}

	function bindHostTracking() {
		if (hostWindow.__avoTxtToolBound) return;
		hostWindow.__avoTxtToolBound = true;

		hostDocument.addEventListener('focusin', function (e) {
			saveTarget(e.target);
		});

		hostDocument.addEventListener('click', function (e) {
			saveTarget(e.target);
		});

		hostDocument.addEventListener('keyup', function (e) {
			saveTarget(e.target);
		});

		hostDocument.addEventListener('mouseup', function (e) {
			saveTarget(e.target);
		});

		hostDocument.addEventListener('selectionchange', function () {
			const active = hostDocument.activeElement;
			if (isUsable(active)) {
				saveTarget(active);
			}
		});
	}

	function findFallbackTarget() {
		const active = hostDocument.activeElement;
		if (isUsable(active)) return active;

		const fixed = hostDocument.getElementById('wr_content');
		if (isUsable(fixed)) return fixed;

		const prefixed = hostDocument.querySelectorAll('textarea[id^="wr_content_"]');
		for (let i = 0; i < prefixed.length; i++) {
			if (isUsable(prefixed[i])) return prefixed[i];
		}

		const all = hostDocument.querySelectorAll('textarea, input[type="text"], input:not([type]), input[type="search"], input[type="url"], input[type="tel"], input[type="email"], input[type="password"], [contenteditable="true"]');
		for (let i = 0; i < all.length; i++) {
			if (isUsable(all[i])) return all[i];
		}

		return null;
	}

	function triggerInputEvent(el) {
		el.dispatchEvent(new Event('input', { bubbles: true }));
		el.dispatchEvent(new Event('change', { bubbles: true }));

		if (typeof hostWindow.autoResize === 'function' && el.tagName && el.tagName.toLowerCase() === 'textarea') {
			hostWindow.autoResize(el);
		}
	}

	function setSelection(el, start, end) {
		if (typeof el.setSelectionRange === 'function') {
			el.focus();
			el.setSelectionRange(start, end);
		}
	}

	function getInsertText(template, selectedText) {
		if (template.indexOf('내용') > -1) {
			return template.replace('내용', selectedText || '내용');
		}
		return template;
	}

	function findPlaceholderRange(insertText) {
		const keyword = '내용';
		const idx = insertText.indexOf(keyword);
		if (idx > -1) {
			return { start: idx, end: idx + keyword.length };
		}

		const titleKeyword = '제목';
		const idx2 = insertText.indexOf(titleKeyword);
		if (idx2 > -1) {
			return { start: idx2, end: idx2 + titleKeyword.length };
		}

		return null;
	}

	function getCurrentTarget() {
		const saved = hostWindow.avoLastTarget;
		if (isUsable(saved)) return saved;
		return findFallbackTarget();
	}

	window.insertTemplate = function (template) {
		const target = getCurrentTarget();

		if (!target) {
			alert('먼저 댓글 작성창이나 수정창을 클릭해 주세요.');
			return;
		}

		if (target.matches('textarea, input')) {
			target.focus();

			let start = typeof target.selectionStart === 'number' ? target.selectionStart : null;
			let end = typeof target.selectionEnd === 'number' ? target.selectionEnd : null;

			if ((start === null || end === null) && hostWindow.avoLastSelection) {
				start = hostWindow.avoLastSelection.start;
				end = hostWindow.avoLastSelection.end;
			}

			if (start === null || end === null) {
				start = target.value.length;
				end = target.value.length;
			}

			const value = target.value;
			const selectedText = value.substring(start, end);
			const insertText = getInsertText(template, selectedText);

			target.value = value.substring(0, start) + insertText + value.substring(end);

			const insertedStart = start;
			const insertedEnd = start + insertText.length;

			if (selectedText.length > 0) {
				setSelection(target, insertedEnd, insertedEnd);
			} else {
				const placeholder = findPlaceholderRange(insertText);
				if (placeholder) {
					setSelection(
						target,
						insertedStart + placeholder.start,
						insertedStart + placeholder.end
					);
				} else {
					setSelection(target, insertedEnd, insertedEnd);
				}
			}

			hostWindow.avoLastTarget = target;
			hostWindow.avoLastSelection = {
				start: target.selectionStart,
				end: target.selectionEnd
			};

			triggerInputEvent(target);
			return;
		}

		if (target.isContentEditable) {
			target.focus();

			const sel = hostWindow.getSelection();
			if (!sel || sel.rangeCount === 0) {
				alert('삽입할 위치를 먼저 클릭해 주세요.');
				return;
			}

			const range = sel.getRangeAt(0);
			const selectedText = sel.toString();
			const insertText = getInsertText(template, selectedText);

			range.deleteContents();
			const textNode = hostDocument.createTextNode(insertText);
			range.insertNode(textNode);

			range.setStartAfter(textNode);
			range.setEndAfter(textNode);
			sel.removeAllRanges();
			sel.addRange(range);

			return;
		}

		alert('지원되지 않는 입력창입니다.');
	};

	document.addEventListener('click', function (e) {
		const preview = e.target.closest('.tool-preview[data-template]');
		if (preview) {
			if (e.target.closest('a')) {
				e.preventDefault();
			}
			insertTemplate(preview.getAttribute('data-template'));
			return;
		}
	});

	bindHostTracking();
})();
</script>

<?php
include_once(G5_PATH.'/tail.sub.php');
?>