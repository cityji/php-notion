<?php
class MarkdownEditor {
    private $content;
    private $filename;

    public function __construct($content = '', $filename = '') {
        $this->content = $content;
        $this->filename = $filename;
    }

    public function render() {
        ob_start();
        ?>
        <div class="markdown-editor h-100">
            <!-- Editor Toolbar -->
            <div class="editor-toolbar d-flex justify-content-between align-items-center">
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-light" onclick="insertMarkdown('**', '**')" title="Bold">
                        <i class="bi bi-type-bold"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-light" onclick="insertMarkdown('*', '*')" title="Italic">
                        <i class="bi bi-type-italic"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-light" onclick="insertMarkdown('# ', '')" title="Heading">
                        <i class="bi bi-type-h1"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-light" onclick="insertMarkdown('- ', '')" title="List">
                        <i class="bi bi-list-ul"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-light" onclick="insertMarkdown('1. ', '')" title="Numbered List">
                        <i class="bi bi-list-ol"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-light" onclick="insertMarkdown('[', '](url)')" title="Link">
                        <i class="bi bi-link-45deg"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-light" onclick="insertMarkdown('```\n', '\n```')" title="Code Block">
                        <i class="bi bi-code-square"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-light" onclick="insertMarkdown('> ', '')" title="Quote">
                        <i class="bi bi-chat-square-quote"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-light" onclick="insertMarkdown('---\n', '')" title="Horizontal Rule">
                        <i class="bi bi-dash-lg"></i>
                    </button>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-light" onclick="togglePreview()" id="previewToggle">
                        <i class="bi bi-eye"></i> Preview
                    </button>
                </div>
            </div>

            <!-- Editor Area -->
            <div id="editorContainer" class="h-100 position-relative">
                <div id="editorWrapper" class="h-100 d-flex editor-active">
                    <div class="line-numbers user-select-none text-muted px-2 py-3 border-end">
                        <!-- Line numbers will be inserted here -->
                    </div>
                    <textarea id="markdownContent" 
                              name="content" 
                              class="form-control border-0 shadow-none py-3"
                              style="resize: none; tab-size: 4;"
                              placeholder="Start typing in markdown..."
                              spellcheck="false"><?php echo htmlspecialchars($this->content); ?></textarea>
                </div>
                
                <div id="previewWrapper" class="h-100" style="display: none;">
                    <div class="preview-loading text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div class="mt-2">Rendering preview...</div>
                    </div>
                    <div id="previewContent" class="preview-content p-4" style="display: none;"></div>
                </div>
            </div>
        </div>

        <style>
            .markdown-editor {
                display: flex;
                flex-direction: column;
                background: var(--bg-white);
                height: 100%;
            }

            .editor-toolbar {
                padding: 0.75rem;
                background: var(--bg-light);
                border-bottom: 1px solid var(--border);
                gap: 0.5rem;
            }

            .editor-toolbar .btn-group {
                background: var(--bg-white);
                padding: 0.25rem;
                border-radius: 0.5rem;
                box-shadow: var(--shadow-sm);
            }

            .editor-toolbar .btn {
                width: 36px;
                height: 36px;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--text-secondary);
                border: none;
                border-radius: 0.375rem;
                transition: var(--transition);
            }

            .editor-toolbar .btn:hover {
                color: var(--primary-main);
                background: var(--primary-light);
                transform: translateY(-1px);
            }

            .editor-toolbar .btn:active {
                transform: translateY(0);
            }

            #editorContainer {
                flex: 1;
                overflow: hidden;
            }

            #editorWrapper {
                height: 100%;
            }

            .line-numbers {
                min-width: 3rem;
                background: var(--bg-light);
                font-family: 'JetBrains Mono', monospace;
                font-size: 0.875rem;
                user-select: none;
            }

            #markdownContent {
                flex: 1;
                overflow-y: auto;
                font-family: 'JetBrains Mono', monospace;
                font-size: 0.875rem;
                line-height: 1.7;
                padding: 1.5rem;
                color: var(--text-primary);
                background: transparent;
                transition: var(--transition);
            }

            #markdownContent:focus {
                outline: none;
                background: var(--bg-subtle);
            }

            #previewWrapper {
                height: 100%;
                overflow-y: auto;
                background: var(--bg-white);
            }

            .preview-content {
                font-family: 'Inter', sans-serif;
            }

            .preview-content {
                max-width: 65ch;
                margin: 0 auto;
                padding: 2rem;
            }

            .preview-content h1,
            .preview-content h2,
            .preview-content h3 {
                margin-top: 2em;
                margin-bottom: 1em;
                font-weight: 600;
                color: var(--text-primary);
                line-height: 1.3;
            }

            .preview-content h1 { font-size: 2.25em; letter-spacing: -0.025em; }
            .preview-content h2 { font-size: 1.75em; letter-spacing: -0.025em; }
            .preview-content h3 { font-size: 1.35em; }

            .preview-content p {
                margin-bottom: 1.5em;
                line-height: 1.8;
            }

            .preview-content pre {
                background: var(--bg-light);
                padding: 1.25em;
                border-radius: 0.75rem;
                overflow-x: auto;
                margin: 1.5em 0;
                box-shadow: var(--shadow-sm);
            }

            .preview-content code {
                font-family: 'JetBrains Mono', monospace;
                font-size: 0.875em;
                padding: 0.2em 0.4em;
                background: var(--bg-subtle);
                border-radius: 0.375rem;
                color: var(--primary-dark);
            }

            .preview-content pre code {
                padding: 0;
                background: transparent;
            }

            .preview-content blockquote {
                border-left: 4px solid var(--primary-light);
                margin: 2em 0;
                padding: 1em 1.5em;
                color: var(--text-secondary);
                background: var(--bg-subtle);
                border-radius: 0 0.5rem 0.5rem 0;
            }

            .preview-content hr {
                border: none;
                border-top: 2px solid var(--border);
                margin: 2em 0;
            }

            .preview-content ul,
            .preview-content ol {
                margin: 1.5em 0;
                padding-left: 2em;
            }

            .preview-content li {
                margin: 0.75em 0;
                padding-left: 0.5em;
            }

            .preview-content li::marker {
                color: var(--primary-main);
            }

            .preview-content a {
                color: var(--primary-main);
                text-decoration: none;
            }

            .preview-content a:hover {
                text-decoration: underline;
            }

            .preview-content table {
                border-collapse: collapse;
                width: 100%;
                margin: 1em 0;
            }

            .preview-content th,
            .preview-content td {
                border: 1px solid var(--border);
                padding: 0.5em 1em;
            }

            .preview-content th {
                background: var(--bg-light);
                font-weight: 500;
            }
        </style>

        <script>
            class MarkdownEditor {
                constructor() {
                    this.editor = document.getElementById('markdownContent');
                    this.lineNumbers = document.querySelector('.line-numbers');
                    this.previewButton = document.getElementById('previewToggle');
                    this.editorWrapper = document.getElementById('editorWrapper');
                    this.previewWrapper = document.getElementById('previewWrapper');
                    this.previewContent = document.getElementById('previewContent');
                    this.previewLoading = document.querySelector('.preview-loading');
                    this.isPreviewMode = false;

                    this.init();
                }

                init() {
                    this.updateLineNumbers();
                    this.setupEventListeners();
                    this.ensureMarkedLoaded();
                }

                setupEventListeners() {
                    // Update line numbers on input
                    this.editor.addEventListener('input', () => this.updateLineNumbers());

                    // Sync scroll between line numbers and editor
                    this.editor.addEventListener('scroll', () => {
                        this.lineNumbers.scrollTop = this.editor.scrollTop;
                    });

                    // Tab key support
                    this.editor.addEventListener('keydown', (e) => {
                        if (e.key === 'Tab') {
                            e.preventDefault();
                            const start = this.editor.selectionStart;
                            const end = this.editor.selectionEnd;
                            this.editor.value = this.editor.value.substring(0, start) + 
                                              '    ' + 
                                              this.editor.value.substring(end);
                            this.editor.selectionStart = this.editor.selectionEnd = start + 4;
                            this.updateLineNumbers();
                        }
                    });
                }

                updateLineNumbers() {
                    const lines = this.editor.value.split('\n');
                    this.lineNumbers.innerHTML = lines
                        .map((_, i) => `<div class="ps-2">${i + 1}</div>`)
                        .join('');
                }

                ensureMarkedLoaded() {
                    if (typeof marked === 'undefined') {
                        const script = document.createElement('script');
                        script.src = 'https://cdn.jsdelivr.net/npm/marked/marked.min.js';
                        script.onload = () => this.initializeMarked();
                        document.head.appendChild(script);
                    } else {
                        this.initializeMarked();
                    }
                }

                initializeMarked() {
                    marked.setOptions({
                        gfm: true,
                        breaks: true,
                        highlight: (code, lang) => {
                            if (Prism && Prism.languages[lang]) {
                                return Prism.highlight(code, Prism.languages[lang], lang);
                            }
                            return code;
                        }
                    });
                }

                togglePreview() {
                    this.isPreviewMode = !this.isPreviewMode;
                    
                    if (this.isPreviewMode) {
                        this.editorWrapper.style.display = 'none';
                        this.previewWrapper.style.display = 'block';
                        this.previewContent.style.display = 'none';
                        this.previewLoading.style.display = 'block';
                        
                        // Small delay to show loading state
                        setTimeout(() => this.updatePreview(), 100);
                    } else {
                        this.editorWrapper.style.display = 'flex';
                        this.previewWrapper.style.display = 'none';
                    }

                    // Update button icon
                    const icon = this.previewButton.querySelector('i');
                    icon.className = this.isPreviewMode ? 'bi bi-pencil' : 'bi bi-eye';
                    this.previewButton.innerHTML = this.isPreviewMode ? 
                        '<i class="bi bi-pencil"></i> Edit' :
                        '<i class="bi bi-eye"></i> Preview';
                }

                updatePreview() {
                    try {
                        const content = this.editor.value;
                        const html = marked(content);
                        this.previewContent.innerHTML = html;
                        Prism.highlightAllUnder(this.previewContent);
                    } catch (error) {
                        this.previewContent.innerHTML = `
                            <div class="alert alert-danger">
                                Error rendering markdown: ${error.message}
                            </div>
                        `;
                    }
                    
                    this.previewLoading.style.display = 'none';
                    this.previewContent.style.display = 'block';
                }

                insertMarkdown(prefix, suffix) {
                    const start = this.editor.selectionStart;
                    const end = this.editor.selectionEnd;
                    const text = this.editor.value;
                    const selectedText = text.substring(start, end);
                    
                    this.editor.value = text.substring(0, start) + 
                                      prefix + 
                                      selectedText + 
                                      suffix + 
                                      text.substring(end);
                    
                    // Reset cursor position
                    const newCursorPos = start + prefix.length + selectedText.length + suffix.length;
                    this.editor.setSelectionRange(newCursorPos, newCursorPos);
                    this.editor.focus();
                    
                    this.updateLineNumbers();
                    if (this.isPreviewMode) {
                        this.updatePreview();
                    }
                }
            }

            // Initialize editor
            document.addEventListener('DOMContentLoaded', () => {
                window.markdownEditor = new MarkdownEditor();
            });

            // Global functions
            function togglePreview() {
                window.markdownEditor.togglePreview();
            }

            function insertMarkdown(prefix, suffix) {
                window.markdownEditor.insertMarkdown(prefix, suffix);
            }
        </script>
        <?php
        return ob_get_clean();
    }
}
