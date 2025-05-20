<?php
// Default values for variables if not set
$page_title = $page_title ?? 'Page Title';
$page_icon = $page_icon ?? 'fas fa-list';
$add_new_text = $add_new_text ?? 'Add New';
$table_headers = $table_headers ?? [];
$items = $items ?? [];
$no_data_message = $no_data_message ?? 'No items found.';
$id_field = $id_field ?? 'id';
$show_header_stats = $show_header_stats ?? false;
$header_stats = $header_stats ?? [];
?>

<?php if ($show_header_stats && !empty($header_stats)): ?>
    <div class="row mb-4">
        <?php foreach ($header_stats as $stat): ?>
            <div class="col-md-3">
                <div class="card bg-<?php echo $stat['color']; ?> text-white">
                    <div class="card-body">
                        <h6 class="card-title"><?php echo $stat['title']; ?></h6>
                        <h3 class="card-text"><?php echo $stat['value']; ?></h3>
                        <small><?php echo $stat['subtitle']; ?></small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="<?php echo $page_icon; ?>"></i> <?php echo $page_title; ?>
            </h1>
            <div>
                <a href="<?php echo SITE_URL; ?>" class="btn btn-secondary back-button">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> <?php echo $add_new_text; ?>
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover" id="dataTable">
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
                                        <?php if (strpos($item[$key], '<span class="badge') === 0): ?>
                                            <?php echo $item[$key]; // Output HTML for badges ?>
                                        <?php else: ?>
                                            <?php echo $item[$key]; ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td>
                                    <div class="action-buttons">
                                        <?php if (function_exists('getActionButtons')): ?>
                                            <?php echo getActionButtons($item); ?>
                                        <?php else: ?>
                                            <a href="edit.php?id=<?php echo $item[$id_field]; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteItem(<?php echo $item[$id_field]; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#dataTable').DataTable({
        destroy: true,
        pageLength: 50,
        order: [[0, 'asc']],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries per page",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
        }
    });
});
</script>
