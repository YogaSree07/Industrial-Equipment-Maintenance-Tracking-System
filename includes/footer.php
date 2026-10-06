        </main>
        <!-- End of Page Container -->

        <!-- Application Footer -->
        <footer class="app-footer">
            <div>
                <strong><?= APP_NAME ?></strong> &mdash; <?= APP_FULL_NAME ?> (v<?= APP_VERSION ?>)
            </div>
            <div>
                <span>Industrial Operations Center &bull; <?= date('Y') ?></span>
            </div>
        </footer>
    </div>
    <!-- End of app-main -->
</div>
<!-- End of app-wrapper -->

<!-- Reusable Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteConfirmModal" role="dialog" aria-modal="true">
    <div class="modal-dialog">
        <div class="modal-body">
            <div class="modal-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 class="modal-title">Confirm Permanent Deletion</h3>
            <p class="modal-desc">
                Are you sure you want to permanently remove <strong id="deleteItemName">this item</strong>?
                <br>All associated records and history may be affected.
            </p>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss-modal>Cancel</button>
                <a href="#" class="btn btn-danger" id="deleteConfirmBtn">
                    <i class="fa-solid fa-trash-can"></i> Yes, Delete
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Core Custom Scripts -->
<script src="<?= url('assets/js/script.js') ?>"></script>

</body>
</html>
