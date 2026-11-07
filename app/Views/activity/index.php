<div class="card">
  <div class="card-header">
    <form class="filters d-flex flex-wrap gap-2" id="activityFilters" role="search">
      <input type="search" name="q" class="form-control form-control-sm" placeholder="Search action or details" aria-label="Search activity">
      <select name="type" class="form-select form-select-sm" aria-label="Type"><option value="">All types</option>
        <?php foreach ($types as $t): ?><option value="<?= e($t) ?>"><?= e(ucfirst($t)) ?></option><?php endforeach; ?></select>
      <select name="user_id" class="form-select form-select-sm" aria-label="User"><option value="">All users</option>
        <?php foreach ($users as $u): ?><option value="<?= (int) $u['id'] ?>"><?= e($u['name']) ?></option><?php endforeach; ?></select>
    </form>
  </div>
  <div id="activityTable"></div>
</div>
