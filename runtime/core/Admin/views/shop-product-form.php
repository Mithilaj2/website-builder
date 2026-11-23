<?php
/** @var ?array $product @var array $images @var array $categories @var array $gstRates @var array $config */
$p = $product ?? ['id' => 0, 'name' => '', 'sku' => '', 'short_description' => '', 'description' => '', 'price' => '', 'compare_price' => null, 'gst_rate' => '5.00', 'hsn' => '', 'track_stock' => 1, 'stock' => 10,
    'option_name' => '', 'badge' => '', 'is_featured' => 0, 'status' => 'active', 'sort_order' => 0, 'category_id' => null, 'variants' => [], 'slug' => ''];
$rupees = static fn ($paise): string => $paise === null || $paise === '' ? '' : rtrim(rtrim(number_format(((int) $paise) / 100, 2, '.', ''), '0'), '.');
$gst = rtrim(rtrim((string) $p['gst_rate'], '0'), '.') ?: '0';
?>
<script type="application/json" id="shopConfig"><?= json_encode(['api' => $config['urls']['api'], 'tinymce' => $config['urls']['tinymce'], 'images' => $images], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <a class="btn btn-sm btn-light" href="<?= e($admin->url('products')) ?>"><i class="bi bi-arrow-left"></i> Products</a>
  <?php if ($p['id']): ?><a class="btn btn-sm btn-light ms-auto" href="<?= e($shopUrl . '/product/' . rawurlencode((string) $p['slug'])) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1"></i>View in shop</a><?php endif; ?>
</div>
<form data-ajax action="<?= e($admin->url($p['id'] ? 'api/products/' . $p['id'] : 'api/products')) ?>" id="productForm" novalidate>
  <div class="alert alert-danger form-alert d-none py-2 small"></div>
  <div class="row g-3">
    <div class="col-xl-8">
      <div class="card mb-3"><div class="card-body">
        <div class="mb-3"><label class="form-label">Product name *</label><input class="form-control" name="name" maxlength="150" required value="<?= e($p['name']) ?>"></div>
        <div class="mb-3"><label class="form-label">Short description</label><textarea class="form-control" name="short_description" rows="2" maxlength="400"><?= e((string) $p['short_description']) ?></textarea><div class="form-text">Shown next to the price.</div></div>
        <div><label class="form-label">Full description</label><textarea class="form-control" name="description" id="prodDesc" rows="8"><?= e((string) $p['description']) ?></textarea></div>
      </div></div>
      <div class="card mb-3">
        <div class="card-header d-flex align-items-center">Images<button type="button" class="btn btn-sm btn-outline-primary ms-auto" id="prodAddImages"><i class="bi bi-images me-1"></i>Add images</button></div>
        <div class="card-body"><div class="prod-images" id="prodImages"></div><div class="form-text">The first image is the main photo. Up to 9 images; square photos (1:1) or 4:5 look best.</div><div class="invalid-feedback d-block" data-err="image"></div><input type="hidden" name="image" disabled></div>
      </div>
      <div class="card mb-3">
        <div class="card-header">Price & tax</div>
        <div class="card-body"><div class="row g-3">
          <div class="col-sm-4"><label class="form-label">Price (₹) *</label><input class="form-control" name="price" inputmode="decimal" required value="<?= e($rupees($p['price'])) ?>"></div>
          <div class="col-sm-4"><label class="form-label">“Was” price (₹)</label><input class="form-control" name="compare_price" inputmode="decimal" value="<?= e($rupees($p['compare_price'])) ?>"><div class="form-text">Shows a strike-through price.</div></div>
          <div class="col-sm-2"><label class="form-label">GST</label><select class="form-select" name="gst_rate"><?php foreach ($gstRates as $r): ?><option value="<?= e($r) ?>"<?= $r === $gst ? ' selected' : '' ?>><?= e($r) ?>%</option><?php endforeach; ?></select></div>
          <div class="col-sm-2"><label class="form-label">HSN</label><input class="form-control" name="hsn" maxlength="8" inputmode="numeric" value="<?= e((string) $p['hsn']) ?>"></div>
        </div></div>
      </div>
      <div class="card mb-3">
        <div class="card-header">Stock & options</div>
        <div class="card-body">
          <div class="row g-3 mb-3">
            <div class="col-sm-4"><label class="form-label">SKU</label><input class="form-control" name="sku" maxlength="60" value="<?= e((string) $p['sku']) ?>"></div>
            <div class="col-sm-4" data-simple-stock><label class="form-label">Stock</label><input class="form-control" name="stock" inputmode="numeric" value="<?= (int) $p['stock'] ?>"></div>
            <div class="col-sm-4 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="track_stock" value="1" id="trackStock"<?= (int) $p['track_stock'] ? ' checked' : '' ?>><label class="form-check-label" for="trackStock">Track stock (stop selling at 0)</label></div></div>
          </div>
          <div class="border rounded p-3 bg-body-tertiary">
            <div class="row g-2 align-items-end mb-2">
              <div class="col-sm-5"><label class="form-label mb-1">Options <span class="text-muted small">(optional: sizes, colours…)</span></label><input class="form-control form-control-sm" name="option_name" maxlength="40" value="<?= e((string) $p['option_name']) ?>" placeholder="Option name, e.g. Size"></div>
              <div class="col-sm-7 text-sm-end"><button type="button" class="btn btn-sm btn-outline-primary" id="addVariant"><i class="bi bi-plus-lg me-1"></i>Add option</button></div>
            </div>
            <div class="invalid-feedback d-block" data-err="variants"></div>
            <table class="table table-sm align-middle mb-0" id="variantTable"<?= $p['variants'] ? '' : ' hidden' ?>>
              <thead><tr><th>Option *</th><th>Price (₹)<div class="fw-normal small text-muted">empty = product price</div></th><th>Stock</th><th>SKU</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($p['variants'] as $i => $v): ?>
                  <tr><td><input type="hidden" name="variants[<?= $i ?>][id]" value="<?= (int) $v['id'] ?>"><input class="form-control form-control-sm" name="variants[<?= $i ?>][label]" value="<?= e($v['label']) ?>" maxlength="60"></td>
                    <td><input class="form-control form-control-sm" name="variants[<?= $i ?>][price]" value="<?= e($rupees($v['price'])) ?>" inputmode="decimal"></td>
                    <td><input class="form-control form-control-sm" name="variants[<?= $i ?>][stock]" value="<?= (int) $v['stock'] ?>" inputmode="numeric" style="max-width:90px"></td>
                    <td><input class="form-control form-control-sm" name="variants[<?= $i ?>][sku]" value="<?= e((string) $v['sku']) ?>" maxlength="60"></td>
                    <td class="text-end"><button type="button" class="btn btn-sm btn-light" data-remove-variant aria-label="Remove option"><i class="bi bi-x-lg"></i></button></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-4">
      <div class="card mb-3 position-sticky" style="top:80px"><div class="card-body">
        <div class="mb-3"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active"<?= $p['status'] === 'active' ? ' selected' : '' ?>>Active (visible in the shop)</option><option value="draft"<?= $p['status'] === 'draft' ? ' selected' : '' ?>>Draft (hidden)</option></select></div>
        <div class="mb-3"><label class="form-label">Category</label><select class="form-select" name="category_id"><option value="">No category</option><?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"<?= (int) $p['category_id'] === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
          <div class="form-text"><a href="<?= e($admin->url('categories')) ?>">Manage categories</a></div></div>
        <div class="mb-3"><label class="form-label">Badge</label><input class="form-control" name="badge" maxlength="30" value="<?= e((string) $p['badge']) ?>" placeholder="e.g. New, Bestseller"></div>
        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_featured" value="1" id="isFeatured"<?= (int) $p['is_featured'] ? ' checked' : '' ?>><label class="form-check-label" for="isFeatured">Featured (shown on the homepage)</label></div>
        <div class="mb-3"><label class="form-label">Sort order</label><input class="form-control" name="sort_order" type="number" value="<?= (int) $p['sort_order'] ?>"></div>
        <button class="btn btn-primary w-100" type="submit"><?= $p['id'] ? 'Save product' : 'Add product' ?></button>
        <?php if ($p['id']): ?><button type="button" class="btn btn-link text-danger w-100 mt-2" data-post="<?= e($admin->url('api/products/' . $p['id'] . '/delete')) ?>?back=1" data-confirm="Delete this product? Past orders keep their details."><i class="bi bi-trash me-1"></i>Delete product</button><?php endif; ?>
      </div></div>
    </div>
  </div>
</form>
<?php include __DIR__ . '/shop-media-modal.php'; ?>
