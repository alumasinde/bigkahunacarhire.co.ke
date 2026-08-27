<?php view('admin/layout-header', ['seo'=>$seo]); ?>
<div class="admin-page-head">
  <div><span class="section-eyebrow">SEARCH ENGINE OPTIMIZATION</span><h2>SEO Pages</h2><p>Manage locations, airports, services and guides without editing PHP.</p></div>
  <a href="<?= base_url('admin/seo-pages/new') ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add SEO Page</a>
</div>

<div class="admin-card">
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th>Page</th><th>Type</th><th>URL</th><th>Indexing</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($pages as $page): ?>
        <tr>
          <td><strong><?= e($page['name']) ?></strong><br><small><?= e($page['title']) ?></small></td>
          <td><?= e(ucfirst($page['page_type'])) ?></td>
          <td><code>/<?= e($page['page_key']) ?></code></td>
          <td><?= (int)$page['is_indexable'] ? 'Index' : 'Noindex' ?></td>
          <td><?= (int)$page['is_active'] ? 'Active' : 'Disabled' ?></td>
          <td class="admin-actions">
            <a href="<?= base_url('admin/seo-pages/'.$page['id'].'/edit') ?>" class="btn btn-sm btn-outline">Edit</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$pages): ?><tr><td colspan="6">No SEO pages yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php view('admin/layout-footer'); ?>
