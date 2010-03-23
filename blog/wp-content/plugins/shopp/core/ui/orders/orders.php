<div class="wrap shopp">
	<h2><?php _e('Orders','Shopp'); ?></h2>

	<form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" id="orders" method="get">
	<?php include("navigation.php"); ?>
	<div>
		<input type="hidden" name="page" value="<?php echo $page; ?>" />
		<input type="hidden" name="status" value="<?php echo $status; ?>" />
	</div>
	<div class="clear"></div>

	<p id="post-search" class="search-box">
		<input type="text" id="orders-search-input" class="search-input" name="s" value="<?php echo attribute_escape($s); ?>" />
		<input type="submit" value="<?php _e('Search Orders','Shopp'); ?>" class="button" />
	</p>
	<ul id="report">
		<li><strong><?php echo $ordercount->total; ?></strong> <span><?php _e('Orders','Shopp'); ?></span></li>
		<li><strong><?php echo money($ordercount->sales); ?></strong> <span><?php _e('Total Sales','Shopp'); ?></span></li>
		<li><strong><?php echo money($ordercount->avgsale); ?></strong> <span><?php _e('Average Sale','Shopp'); ?></span></li>
	</ul>
	
	<div class="tablenav">
		<div class="alignleft actions">
			<button type="submit" id="delete-button" name="deleting" value="order" class="button-secondary"><?php _e('Delete','Shopp'); ?></button>
			<select name="newstatus">
				<?php echo menuoptions($statusLabels,false,true); ?>
			</select>
			<button type="submit" id="update-button" name="update" value="order" class="button-secondary"><?php _e('Update','Shopp'); ?></button>
			<span class="filtering">
			<select name="range" id="range">
				<?php echo menuoptions($ranges,$range,true); ?>
			</select>
			<span id="dates">
			<div id="start-position" class="calendar-wrap"><input type="text" id="start" name="start" value="<?php echo $startdate; ?>" size="10" class="search-input selectall" /></div>
			<small>to</small>
			<div id="end-position" class="calendar-wrap"><input type="text" id="end" name="end" value="<?php echo $enddate; ?>" size="10" class="search-input selectall" /></div>
			</span>
			<button type="submit" id="filter-button" name="filter" value="order" class="button-secondary"><?php _e('Filter','Shopp'); ?></button>
			</span>
			</div>
			<?php if ($page_links) echo "<div class='tablenav-pages'>$page_links</div>"; ?>
		<div class="clear"></div>
	</div>
	<?php if (SHOPP_WP27): ?><div class="clear"></div>
	<?php else: ?><br class="clear" /><?php endif; ?>

	<table class="widefat" cellspacing="0">
		<thead>
		<tr><?php shopp_print_column_headers('toplevel_page_shopp-orders'); ?></tr>
		</thead>
		<?php if (SHOPP_WP27): ?>
		<tfoot>
		<tr><?php shopp_print_column_headers('toplevel_page_shopp-orders',false); ?></tr>
		</tfoot>
		<?php endif; ?>
	<?php if (sizeof($Orders) > 0): ?>
		<tbody id="orders-table" class="list orders">
		<?php 
			if (SHOPP_WP27) $hidden = get_hidden_columns('toplevel_page_shopp-orders');
 			else $hidden = array();
			
			$even = false; foreach ($Orders as $Order): 

			$classes = array();
			
			$txnstatus = $Order->transtatus;
			if (array_key_exists($Order->transtatus,$txnStatusLabels)) $txnstatus = $txnStatusLabels[$Order->transtatus];
			if (empty($txnstatus)) $txnstatus = "UNKNOWN";
			$classes[] = strtolower(preg_replace('/[^\w]/','_',$txnstatus));

			if (!$even) $classes[] = "alternate"; 
			do_action_ref_array('shopp_order_row_css',array(&$classes,&$Order));
			$even = !$even;
			?>
		<tr class="<?php echo join(' ',$classes); ?>">
			<th scope='row' class='check-column'><input type='checkbox' name='selected[]' value='<?php echo $Order->id; ?>' /></th>
			<td class="order column-order<?php echo in_array('order',$hidden)?' hidden':''; ?>"><?php echo $Order->id; ?></td>
			<td class="name column-name"><a class='row-title' href='<?php echo add_query_arg(array('page'=>$this->Admin->orders,'id'=>$Order->id),$Shopp->wpadminurl."admin.php"); ?>' title='<?php _e('View','Shopp'); ?> &quot;<?php echo $Order->id; ?>&quot;'><?php echo (empty($Order->firstname) && empty($Order->lastname))?"(".__('no contact name','Shopp').")":"{$Order->firstname} {$Order->lastname}"; ?></a><?php echo !empty($Order->company)?"<br />$Order->company":""; ?></td>
			<td class="destination column-destination<?php echo in_array('destination',$hidden)?' hidden':''; ?>"><?php 
				$location = '';
				$location = $Order->shipcity;
				if (!empty($location) && !empty($Order->shipstate)) $location .= ', ';
				$location .= $Order->shipstate;
				if (!empty($location) && !empty($Order->shipcountry))
					$location .= ' &mdash; ';
				$location .= $Order->shipcountry;
				echo $location;
				 ?></td>
			<td class="total column-total<?php echo in_array('total',$hidden)?' hidden':''; ?>"><?php echo money($Order->total); ?></td>
			<td class="txn column-txn<?php echo in_array('txn',$hidden)?' hidden':''; ?>"><?php echo $Order->transactionid; ?><br /><strong><?php echo $Order->gateway; ?></strong> &mdash; <?php echo $txnstatus; ?></td>
			<td class="date column-date<?php echo in_array('date',$hidden)?' hidden':''; ?>"><?php echo date("Y/m/d",mktimestamp($Order->created)); ?><br />
				<strong><?php echo $statusLabels[$Order->status]; ?></strong></td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	<?php else: ?>
		<tbody><tr><td colspan="6"><?php _e('No','Shopp'); ?><?php if (!empty($_GET['status'])) echo ' '.strtolower($statusLabels[$_GET['status']]); ?> <?php _e('orders, yet.','Shopp'); ?></td></tr></tbody>
	<?php endif; ?>
	</table>
	
	</form>
	
	<div class="tablenav">
		<div class="alignleft actions">
			<form action="<?php echo esc_url(add_query_arg(array_merge($_GET,array('lookup'=>'purchaselog')),$Shopp->wpadminurl."admin.php")); ?>" id="log" method="post">
			<button type="button" id="export-settings-button" name="export-settings" class="button-secondary"><?php _e('Export Options','Shopp'); ?></button>
			<span id="export-settings" class="hidden">
			<div id="export-columns" class="multiple-select">
				<ul>
					<li<?php $even = true; if ($even) echo ' class="odd"'; $even = !$even; ?>><input type="checkbox" name="selectall_columns" id="selectall_columns" /><label for="selectall_columns"><strong><?php _e('Select All','Shopp'); ?></strong></label></li>	
					<li<?php if ($even) echo ' class="odd"'; $even = !$even; ?>><input type="hidden" name="settings[purchaselog_headers]" value="off" /><input type="checkbox" name="settings[purchaselog_headers]" id="purchaselog_headers" value="on" /><label for="purchaselog_headers"><strong><?php _e('Include column headings','Shopp'); ?></strong></label></li>	
					
					<?php $even = true; foreach ($columns as $name => $label): ?>
						<li<?php if ($even) echo ' class="odd"'; $even = !$even; ?>><input type="checkbox" name="settings[purchaselog_columns][]" value="<?php echo $name; ?>" id="column-<?php echo $name; ?>" <?php echo in_array($name,$selected)?' checked="checked"':''; ?> /><label for="column-<?php echo $name; ?>" ><?php echo $label; ?></label></li>
					<?php endforeach; ?>
					
				</ul>
			</div>
			<?php PurchasesIIFExport::settings(); ?>
			<br />
			<select name="settings[purchaselog_format]" id="purchaselog-format">
				<?php echo menuoptions($exports,$formatPref,true); ?>
			</select></span>
			<button type="submit" id="download-button" name="download" value="export" class="button-secondary"><?php _e('Download','Shopp'); ?></button>
		</div>
		<?php if ($page_links) echo "<div class='tablenav-pages'>$page_links</div>"; ?>
		<div class="clear"></div>
	</div>
</div>
  
<div id="start-calendar" class="calendar"></div>
<div id="end-calendar" class="calendar"></div>

<script type="text/javascript">
helpurl = "<?php echo SHOPP_DOCS; ?>Managing Orders";
var lastexport = new Date(<?php echo date("Y,(n-1),j",$Shopp->Settings->get('purchaselog_lastexport')); ?>);

jQuery(document).ready( function() {
var $=jQuery.noConflict();
	
$('#selectall').change( function() {
	$('#orders-table th input').each( function () {
		if (this.checked) this.checked = false;
		else this.checked = true;
	});
});

$('#delete-button').click(function() {
	if (confirm("<?php echo addslashes(__('Are you sure you want to delete the selected orders?','Shopp')); ?>")) return true;
	else return false;
});

$('#update-button').click(function() {
	if (confirm("<?php echo addslashes(__('Are you sure you want to update the status of the selected orders?','Shopp')); ?>")) return true;
	else return false;
});

function getDateInput(input) {
	var match = false;
	match = $(input).get(0).value.match(/^(\d{1,2}).{1}(\d{1,2}).{1}(\d{4})/);
	if (match) return new Date(match[3],(match[1]-1),match[2]);
	return false;
}

function formatDate (e) {
	if (this.value == "") match = false;
	if (this.value.match(/^(\d{6,8})/))
		match = this.value.match(/(\d{1,2}?)(\d{1,2})(\d{4,4})$/);
	else if (this.value.match(/^(\d{1,2}.{1}\d{1,2}.{1}\d{4})/))
		match = this.value.match(/^(\d{1,2}).{1}(\d{1,2}).{1}(\d{4})/);
	if (match) this.setDate(new Date(match[3],(match[1]-1),match[2]));
	$('#start-calendar, #end-calendar').hide();
}

function setDate(date,calendar) {
	$(this).val((date.getMonth()+1)+"/"+date.getDate()+"/"+date.getFullYear());
	if (calendar) {
		calendar.render(date.getMonth()+1,date.getDate(),date.getFullYear());
		calendar.selection = date;
		calendar.autoselect();
	}
}

var start = $('#start');
var startdate = getDateInput(start);
var StartCalendar = new PopupCalendar($('#start-calendar'));
StartCalendar.scheduling = false;
if (startdate) {
	StartCalendar.render(startdate.getMonth()+1,startdate.getDate(),startdate.getFullYear());
	StartCalendar.selection = startdate;
	StartCalendar.autoselect();
} else StartCalendar.render();

start.setDate = setDate;
start.get(0).setDate = setDate;
start.calendar = StartCalendar;
start.change(formatDate);


var end = $('#end');
var enddate = getDateInput(end);
var EndCalendar = new PopupCalendar($('#end-calendar'));
EndCalendar.scheduling = false;
if (enddate) {
	EndCalendar.render(enddate.getMonth()+1,enddate.getDate(),enddate.getFullYear());
	EndCalendar.selection = enddate;
	EndCalendar.autoselect();	
} else EndCalendar.render();

end.setDate = setDate;
end.get(0).setDate = setDate;
end.calendar = EndCalendar;
end.change(formatDate);

var scpos = $('#start-position').offset();
$('#start-calendar').hide()
	.css({left:scpos.left,
		   top:scpos.top+$('#start-position').height()+10});

$('#start').click(function (e) {
	$('#end-calendar').hide();
	$('#start-calendar').toggle();
	$(StartCalendar).change(function () {
		$('#start').val((StartCalendar.selection.getMonth()+1)+"/"+
			StartCalendar.selection.getDate()+"/"+
			StartCalendar.selection.getFullYear());
	});
});

var ecpos = $('#end-position').offset();
$('#end-calendar').hide()
	.css({left:ecpos.left,
		   top:ecpos.top+$('#end-position input').height()+10});
		
$('#end').click(function (e) {
	$('#start-calendar').hide();
	$('#end-calendar').toggle();
	$(EndCalendar).change(function () {
		$('#end').val((EndCalendar.selection.getMonth()+1)+"/"+
			EndCalendar.selection.getDate()+"/"+
			EndCalendar.selection.getFullYear());
	});
});

$('#range').change(function () {
	if (this.selectedIndex == 0) {
		start.val(''); end.val('');
		$('#dates').addClass('hidden');
		return;
	} else $('#dates').removeClass('hidden');
	var today = new Date();
	var startdate = getDateInput($('#start'));
	var enddate = getDateInput($('#end'));
	if (!startdate) startdate = new Date(today.getFullYear(),today.getMonth(),today.getDate());
	if (!enddate) enddate = new Date(today.getFullYear(),today.getMonth(),today.getDate());
	today = new Date(today.getFullYear(),today.getMonth(),today.getDate());
	switch($(this).val()) {
		case 'week': 
			startdate.setDate(today.getDate()-today.getDay());
			enddate = new Date(startdate.getFullYear(),startdate.getMonth(),startdate.getDate()+6);
			break;
		case 'month':
			startdate.setDate(1);
			enddate = new Date(startdate.getFullYear(),startdate.getMonth()+1,0);
			break;
		case 'quarter':
			quarter = Math.floor(today.getMonth()/3);
			startdate = new Date(today.getFullYear(),today.getMonth()-(today.getMonth()%3),1);
			enddate = new Date(today.getFullYear(),startdate.getMonth()+3,0);
			break;
		case 'year':
			startdate = new Date(today.getFullYear(),0,1);
			enddate = new Date(today.getFullYear()+1,0,0);
			break;
		case 'yesterday':
			startdate.setDate(today.getDate()-1);
			enddate.setDate(today.getDate()-1);
			break;
		case 'lastweek':
			startdate.setDate(today.getDate()-today.getDay()-7);
			enddate.setDate((today.getDate()-today.getDay()+6)-7);
			break;
		case 'last30':
			startdate.setDate(today.getDate()-30);
			enddate.setDate(today.getDate());
			break;
		case 'last90':
			startdate.setDate(today.getDate()-90);
			enddate.setDate(today.getDate());
			break;
		case 'lastmonth':
			startdate = new Date(today.getFullYear(),today.getMonth()-1,1);
			enddate = new Date(today.getFullYear(),today.getMonth(),0);
			break;
		case 'lastquarter':
			startdate = new Date(today.getFullYear(),(today.getMonth()-(today.getMonth()%3))-3,1);
			enddate = new Date(today.getFullYear(),startdate.getMonth()+3,0);
			break;
		case 'lastyear':
			startdate = new Date(today.getFullYear()-1,0,1);
			enddate = new Date(today.getFullYear(),0,0);
			break;
		case 'lastexport':
			startdate = lastexport;
			enddate = today;
			break;
		case 'custom': break;
	}
	start.setDate(startdate,StartCalendar); end.setDate(enddate,EndCalendar);
}).change();

$('#export-settings-button').click(function () { $('#export-settings-button').hide(); $('#export-settings').removeClass('hidden'); });
$('#selectall_columns').change(function () { 
	if ($(this).attr('checked')) $('#export-columns input').not(this).attr('checked',true); 
	else $('#export-columns input').not(this).attr('checked',false); 
});

<?php if (SHOPP_WP27): ?>
pagenow = 'toplevel_page_shopp-orders';
columns.init(pagenow);
<?php endif; ?>

});

</script>