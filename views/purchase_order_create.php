<table class="table table-bordered">
  <thead>
    <tr>
      <th>Description</th>
      <th>SKU</th>
      <!-- Add other headers as needed -->
    </tr>
  </thead>
  <tbody>
    <?php foreach ($mr_items as $i => $item): ?>
      <tr>
        <td>
          <input type="text" name="description[]" class="form-control"
                value="<?= htmlspecialchars($item['description']) ?>" required>
          <input type="hidden" name="item_id[]" value="<?= $item['master_item_id'] ?>">
          <input type="hidden" name="item_type[]" value="<?= $item['master_item_type'] ?>">
        </td>
        <td>
          <input type="text" name="sku[]" class="form-control" value="<?= htmlspecialchars($item['sku']) ?>">
        </td>
        <!-- Add other columns as needed -->
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
