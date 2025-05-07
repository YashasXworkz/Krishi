<?php
// Return reasons dropdown options
$return_reasons = [
    "Damaged product", 
    "Quality not as expected", 
    "Wrong item received", 
    "Not satisfied with the product",
    "Other"
];
?>

<!-- Return Modal -->
<div class="modal fade" id="returnModal" tabindex="-1" role="dialog" aria-labelledby="returnModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="returnModalLabel">Return Order</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="./php/returns/process_return.php" method="POST">
        <div class="modal-body">
          <input type="hidden" id="return_orderid" name="orderid" value="">
          <input type="hidden" id="return_farmerid" name="farmerid" value="">
          <input type="hidden" id="return_prodid" name="prodid" value="">
          
          <div class="form-group">
            <label for="return_product">Product:</label>
            <input type="text" class="form-control" id="return_product" readonly>
          </div>
          
          <div class="form-group">
            <label for="reason">Reason for Return:</label>
            <select class="form-control" name="reason" required>
              <option value="">Select reason</option>
              <?php foreach($return_reasons as $reason): ?>
              <option value="<?php echo htmlspecialchars($reason); ?>"><?php echo htmlspecialchars($reason); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="alert alert-info">
            <small>
              <i class="fa fa-info-circle"></i> 
              Returns are only allowed for products that have been delivered and within 24 hours of delivery.
              Once your return request is processed, the farmer will be notified.
            </small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" name="return_order" class="btn btn-danger">Submit Return Request</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- JavaScript to populate the return modal -->
<script>
function openReturnModal(orderid, farmerid, prodid, prodname) {
  document.getElementById('return_orderid').value = orderid;
  document.getElementById('return_farmerid').value = farmerid;
  document.getElementById('return_prodid').value = prodid;
  document.getElementById('return_product').value = prodname;
  $('#returnModal').modal('show');
}
</script> 