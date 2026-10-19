import os
import re

def sanitize_text(text):
    text = text.replace("\\vert", "||")
    text = text.replace("\x0b", "\\v").replace("\x0c", "\\f").replace("\u00a0", " ")
    text = re.sub(r"[\x00-\x08\x0b\x0c\x0e-\x1f]", "", text)
    return text.replace("\r\n", "\n")

def update_file(path, transform_fn, desc):
    if not os.path.exists(path):
        return
    with open(path, "r", encoding="utf-8", errors="ignore") as f:
        original = f.read()
    updated = sanitize_text(transform_fn(original))
    if updated != original:
        with open(path, "w", encoding="utf-8", newline="\n") as f:
            f.write(updated)
        print(f"[CLEANED] {desc}: {path}")
    else:
        print(f"[OK] {desc} already clean: {path}")

# 1. Remove `Announcements, FAQs & Reviews` (/addons/page-builder/content-hub) from Sidebar
def clean_sidebar(code):
    # Remove any <a> or <li> block linking to addons/page-builder/content-hub
    code = re.sub(
        r"<li[^>]*>\s*<a[^>]*addons/page-builder/content-hub[\s\S]*?</li>\s*",
        lambda m: "",
        code
    )
    code = re.sub(
        r"<a[^>]*addons/page-builder/content-hub[\s\S]*?</a>\s*",
        lambda m: "",
        code
    )
    return code

for sb_path in [
    os.path.join("app", "Views", "layouts", "partials", "sidebar.php"),
    os.path.join("app", "Views", "layouts", "master_layout.php"),
    os.path.join("app", "Views", "partials", "sidebar.php")
]:
    update_file(sb_path, clean_sidebar, "Removed legacy Content Hub link from Sidebar")

# 2. Remove `Announcements, FAQs & Reviews` button & bottom legacy cards from `pages_list_view.php`
def clean_pages_list(code):
    # Remove top button linking to content-hub
    code = re.sub(
        r"<a[^>]*addons/page-builder/content-hub[^>]*>[\s\S]*?</a>\s*",
        lambda m: "",
        code
    )
    # Remove any bottom legacy CRUD sections after the main pages table section
    marker = "<!-- ==========================================================================\n     DEDICATED DATABASE MANAGERS"
    if marker in code:
        code = code.split(marker)[0] + "<?= $this->endSection() ?>\n"
    return code

for pl_path in [
    os.path.join("app", "Views", "addons", "page_builder", "pages_list_view.php"),
    os.path.join("writable", "addons", "extracted", "page_builder", "Views", "addons", "page_builder", "pages_list_view.php")
]:
    update_file(pl_path, clean_pages_list, "Removed legacy Content Hub button from pages_list_view.php")

# 3. Clean `PageBuilderController.php`: Redirect `/addons/page-builder/content-hub` safely & remove legacy methods
def clean_pb_controller(code):
    # Replace contentHub() method so if anyone visits the old URL, it redirects to Page Builder cleanly
    pattern = r"public function contentHub\(\)\s*\{[\s\S]*?\n    \}"
    replacement = r"""public function contentHub()
    {
        return redirect()->to(base_url('addons/page-builder'))
            ->with('pb_notice', 'Announcements, FAQs, and Reviews are now managed via their dedicated independent Add-On modules.');
    }"""
    if re.search(pattern, code):
        code = re.sub(pattern, lambda m: replacement, code, count=1)
    return code

for ctrl_path in [
    os.path.join("app", "Controllers", "Addons", "PageBuilder", "PageBuilderController.php"),
    os.path.join("writable", "addons", "extracted", "page_builder", "Controllers", "Addons", "PageBuilder", "PageBuilderController.php")
]:
    update_file(ctrl_path, clean_pb_controller, "Cleaned legacy contentHub() in PageBuilderController.php")

# 4. Delete obsolete `content_hub_view.php` if it exists
for ch_view in [
    os.path.join("app", "Views", "addons", "page_builder", "content_hub_view.php"),
    os.path.join("writable", "addons", "extracted", "page_builder", "Views", "addons", "page_builder", "content_hub_view.php")
]:
    if os.path.exists(ch_view):
        os.remove(ch_view)
        print(f"[REMOVED] Deleted obsolete legacy view: {ch_view}")

print("================================================================")
print("LEGACY CONTENT HUB & SIDEBAR CLEANUP COMPLETE!")
print("================================================================")