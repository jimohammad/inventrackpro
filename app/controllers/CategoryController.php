<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Item.php';

class CategoryController extends BaseController {
    private Item $itemModel;
    private \PDO $db;

    public function __construct() {
        parent::__construct();
        $this->itemModel = new Item();
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(): void {
        Auth::authorize('inventory', 'view');

        $db = Database::getInstance();
        $categories = $db->fetchAll(
            "SELECT c.*, p.name as parent_name,
                    (SELECT COUNT(*) FROM items i WHERE i.category_id = c.id) as item_count
             FROM categories c
             LEFT JOIN categories p ON p.id = c.parent_id
             ORDER BY c.name ASC"
        );

        $pageTitle = 'Item Categories';
        $page      = 'categories';

        ob_start();
        include __DIR__ . '/../views/inventory/categories.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function store(): void {
        Auth::authorize('inventory', 'add');
        if (!$this->isPost()) { $this->redirect('?page=categories'); }

        $name      = trim($this->input('name'));
        $parentId  = $this->inputInt('parent_id') ?: null;
        $desc      = trim($this->input('description'));

        if (!$name) {
            $this->flash('error', 'Category name is required.');
            $this->redirect('?page=categories');
        }

        $db = Database::getInstance();
        $db->execute(
            "INSERT INTO categories (name, parent_id, description) VALUES (?, ?, ?)",
            [$name, $parentId, $desc]
        );

        $this->flash('success', "Category '{$name}' created.");
        $this->redirect('?page=categories');
    }

    public function update(): void {
        Auth::authorize('inventory', 'edit');
        if (!$this->isPost()) { $this->redirect('?page=categories'); }

        $id       = $this->inputInt('id');
        $name     = trim($this->input('name'));
        $parentId = $this->inputInt('parent_id') ?: null;
        $desc     = trim($this->input('description'));

        // Prevent setting self as parent
        if ($parentId === $id) $parentId = null;

        $db = Database::getInstance();
        $db->execute(
            "UPDATE categories SET name=?, parent_id=?, description=? WHERE id=?",
            [$name, $parentId, $desc, $id]
        );

        $this->flash('success', 'Category updated.');
        $this->redirect('?page=categories');
    }

    public function delete(): void {
        Auth::authorize('inventory', 'delete');

        // AUDIT FIX S5: Require POST for destructive action
        if (!$this->isPost()) {
            $this->flash('error', 'Invalid request method.');
            $this->redirect('?page=categories');
            return;
        }

        $id = $this->inputInt('id');
        $db = Database::getInstance();

        // Check if category has items
        $count = $db->fetchOne("SELECT COUNT(*) as c FROM items WHERE category_id = ?", [$id]);
        if ($count['c'] > 0) {
            $this->flash('error', "Cannot delete — {$count['c']} item(s) are using this category.");
            $this->redirect('?page=categories');
        }

        // Move child categories to no parent
        $db->execute("UPDATE categories SET parent_id = NULL WHERE parent_id = ?", [$id]);
        $db->execute("DELETE FROM categories WHERE id = ?", [$id]);

        $this->flash('success', 'Category deleted.');
        $this->redirect('?page=categories');
    }
}
