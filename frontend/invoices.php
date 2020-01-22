<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 
//echo "<pre>"; print_r($results); die('Result');
if(!empty($results)){
?>
<div class="container" style="padding: 0px 157px;">
<!-- Simple Invoice - START -->
<div class="container">
    <div class="row">
        <div class="col-xs-12">
            <div class="text-center">
                <i class="fa fa-search-plus pull-left icon"></i>
                <h2>Invoice #<?php echo $results[0]->bill_no; ?></h2>
            </div>
            <hr>
            <div class="row">
                <div class="col-xs-12 col-md-4 col-lg-4 pull-left">
                    <div class="panel panel-default height">
                        <div class="panel-heading">Customer Detail:</div>
                        <div class="panel-body">
                            <strong><?php echo ucfirst($results[0]->customer_name); ?>:</strong><br>
                            Email: <?php echo $results[0]->email; ?><br>
                            Phone: <?php echo $results[0]->phone; ?><br>
                            Mandant: <?php echo $results[0]->mandant; ?><br>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="text-center"><strong>Invoice summary</strong></h3>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-condensed">
                            <thead>
                                <tr>
                                    <td class="text-center"><strong>Name der Position</strong></td>
                                    <td class="text-center"><strong>Typ</strong></td>
                                    <td class="text-right"><strong>Qty</strong></td>
                                    <td class="text-right"><strong>Price</strong></td>
                                    <td class="text-right"><strong>Total</strong></td>
                                </tr>
                            </thead>
                            <tbody>
							<?php $total_price = 0; 
							foreach($results as $res){ ?>
                                <tr>
                                    <td class="text-center"><?php echo $res->position_name; ?></td>
                                    <td class="text-center"><?php echo $res->typ; ?></td>
                                    <td class="text-right"><?php echo $res->qty; ?></td>
                                    <td class="text-right">$<?php echo $price = number_format($res->price, 2, '.', ''); ?></td>
                                    <td class="text-right">$<?php echo $total = number_format($res->price*$res->qty, 2, '.', ''); ?></td>
                                </tr>
							<?php
								  $total_price+= $total;
								}  ?> 
								<tr>
                                    <td class="emptyrow"><i class="fa fa-barcode iconbig"></i></td>
                                    <td class="emptyrow"></td>
                                    <td class="emptyrow"></td>
                                    <td class="emptyrow text-center"><strong>Total</strong></td>
                                    <td class="emptyrow text-right">$<?php echo $total = number_format($total_price, 2, '.', ''); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.height {
    min-height: 200px;
}

.icon {
    font-size: 47px;
    color: #5CB85C;
}

.iconbig {
    font-size: 77px;
    color: #5CB85C;
}

.table > tbody > tr > .emptyrow {
    border-top: none;
}

.table > thead > tr > .emptyrow {
    border-bottom: none;
}

.table > tbody > tr > .highrow {
    border-top: 3px solid;
}
</style>
<!-- Simple Invoice - END -->
</div>
<?php }else{ ?>
<div class="container" style="padding: 0px 157px;">
<!-- Simple Invoice - START -->
<div class="container">
    <div class="row">
        <div class="col-xs-12">
            <div class="text-center">
                <i class="fa fa-search-plus pull-left icon"></i>
                <h2>No invoice with this number found. Please check the link.</h2>
            </div>
         </div>
      </div>
   </div>
 </div>
			


<?php 
}