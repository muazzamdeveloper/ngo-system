{{-- Classic editor (Visual and Text tabs). Expects $name, $value (already cleaned), $label. --}}
<div class="classic-editor" data-classic-editor>
    <div class="ce-tabs">
        <button type="button" data-tab="visual" class="is-active">Visual</button>
        <button type="button" data-tab="text">Text</button>
    </div>
    <div class="ce-toolbar">
        <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
        <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
        <button type="button" data-cmd="underline" title="Underline"><u>U</u></button>
        <button type="button" data-cmd="strikeThrough" title="Strikethrough"><s>S</s></button>
        <span class="ce-sep"></span>
        <select data-block title="Format">
            <option value="p">Paragraph</option>
            <option value="h2">Heading 2</option>
            <option value="h3">Heading 3</option>
            <option value="h4">Heading 4</option>
            <option value="blockquote">Quote</option>
        </select>
        <span class="ce-sep"></span>
        <button type="button" data-cmd="insertUnorderedList" title="Bulleted list">&bull; List</button>
        <button type="button" data-cmd="insertOrderedList" title="Numbered list">1. List</button>
        <span class="ce-sep"></span>
        <button type="button" data-link title="Insert link">Link</button>
        <button type="button" data-cmd="unlink" title="Remove link">Unlink</button>
        <button type="button" data-cmd="insertHorizontalRule" title="Horizontal line">&mdash;</button>
        <span class="ce-sep"></span>
        <button type="button" data-cmd="removeFormat" title="Clear formatting">Clear</button>
        <button type="button" data-cmd="undo" title="Undo">&#8630;</button>
        <button type="button" data-cmd="redo" title="Redo">&#8631;</button>
    </div>
    <div class="ce-visual" contenteditable="true" role="textbox" aria-multiline="true" aria-label="{{ $label }}"></div>
    <textarea class="ce-text" name="{{ $name }}" rows="14">{{ $value }}</textarea>
</div>
