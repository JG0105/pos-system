<?php
// Default values for variables if not set
$page_title = $page_title ?? 'Page Title';
$page_icon = $page_icon ?? 'fas fa-list';
$add_new_text = $add_new_text ?? 'Add New';
$table_headers = $table_headers ?? [];
$items = $items ?? [];
$no_data_message = $no_data_message ?? 'No items found.';
?>

<div class="page-header">
    <h1><i class="<?php echo $page_icon; ?>"></i> <?php echo $page_title; ?></h1>
    <div>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> <?php echo $add_new_text; ?>
        </a>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <?php foreach ($table_headers as $header): ?>
                    <th><?php echo $header; ?></th>
                <?php endforeach; ?>
                <th width="120">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="<?php echo count($table_headers) + 1; ?>" class="text-center">
                        <?php echo $no_data_message; ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <?php foreach ($table_headers as $key => $header): ?>
                            <td>
                                <?php if ($key === 'status'): ?>
                                    <span class="badge bg-<?php echo $item[$key] == 'Active' ? 'success' : 'danger'; ?>">
                                        <?php echo $item[$key]; ?>
                                    </span>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($item[$key]); ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td>
                            <div class="action-buttons">
                                <a href="view.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-success">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteItem(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include ROOT_PATH . '/templates/delete_modal.php'; ?>
