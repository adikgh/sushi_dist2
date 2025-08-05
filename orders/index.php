<? include "../config/core.php";

	// 
	if (!$user_id) header('location: /');
	if (@$user_staff['positions_id'] == 6) {
		$core->user_unset();
		header('location: /');
	}


	$cmp = fun::company($company);
	$open = true; $result = 0;
	if ($cmp['ins_dt'] != null && $cmp['end_dt'] != null) {
		$result = intval((strtotime($cmp['end_dt']) - strtotime(date("d.m.Y"))) / (60*60*24));
		if ($result <= 0) header('location: http://kassa.abdis.kz/pay.php');
	}



   	$type = @$_GET['type'];
   	$sort = 'new'; if (@$_GET['sort']) $sort = @$_GET['sort'];
	if (@$_GET['branch']) $branch = @$_GET['branch'];

	
	// $start_cdate = '2025-03-07';


	// 
	if ($sort == 'new') {
		$menu_name = 'car';
		$orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and `paid` = 1 and company_id = '$company' and `order_type` = 1 and `сourier_id` is null and `order_status` in(1, 2) order by number asc");
	} elseif ($sort == 'road' && @$_GET['staff']) {
		$menu_name = 'car';
		$сourier_id = $_GET['staff'];
		$orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and `paid` = 1 and company_id = '$company' and `order_type` = 1 and `order_status` = 3 and `сourier_id` = '$сourier_id' order by number desc");
	} elseif ($sort == 'road') {
		$menu_name = 'car';
		$orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and `paid` = 1 and company_id = '$company' and `order_type` = 1 and `order_status` = 3 and `сourier_id` is not null order by number desc");
	} elseif ($sort == 'history' && @$_GET['staff']) {
		$menu_name = 'car';
		$сourier_id = $_GET['staff'];
		$orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and `paid` = 1 and company_id = '$company' and `order_type` = 1 and `order_status` = 4 and  `сourier_id` = '$сourier_id' order by number desc");
	} elseif ($sort == 'history') {
		$menu_name = 'car';
		$orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and `paid` = 1 and company_id = '$company' and `order_type` = 1 and `order_status` = 4 and `сourier_id` is not null order by number desc");
	} elseif ($sort == 'myself') {
		$menu_name = 'user';
		$orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and `paid` = 1 and company_id = '$company' and `order_type` = 2 and `order_status` in(1, 2) order by number desc");
	} elseif ($sort == 'myself_yes') {
		$menu_name = 'user';
		$orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and `paid` = 1 and company_id = '$company' and `order_type` = 2 and `order_status` = 4 order by number desc");
	} elseif ($sort == 'coffee') {
		$menu_name = 'coffee';
		$orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and `paid` = 1 and company_id = '$company' and `order_type` = 4 order by number desc");
	} else {
		$menu_name = 'none';
		$orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and `paid` = 1  and company_id = '$company' and `order_status` in(5, 6) order by number desc");
	}



	$allorder['total'] = 0;
	$allorder['number'] = 0;
	$allorder['pay_qr'] = 0;
	$allorder['pay_delivery'] = 0;


	// site setting
	$pod_menu_name = $sort;
	$css = ['orders'];
	$js = ['orders'];
?>
<? include "../block/header.php"; ?>

	<div class="">

		<div class="hil_head">
			<div class="bl_c">

				<div class="hil_headc">

					<? if ($menu_name == 'car'): ?> <h4 class="hil_headc1 txt_c">Доставка</h4>
					<? elseif ($menu_name == 'user'): ?> <h4 class="hil_headc1 txt_c">Собой</h4>
					<? elseif ($menu_name == 'none'): ?> <h4 class="hil_headc1 txt_c">Отказ</h4> <? endif ?>

					<? if ($sort == 'new' || $sort == 'road' || $sort == 'history'): ?>
						<div class="hil_fr1">
							<a class="hil_fr1c <?=($sort == 'new'?'hil_fr1c_act':'')?>" href="/orders/?sort=new">Жаңа</a>
							<a class="hil_fr1c <?=($sort == 'road'?'hil_fr1c_act':'')?>" href="/orders/?sort=road">Жолда</a>
							<a class="hil_fr1c <?=($sort == 'history'?'hil_fr1c_act':'')?>" href="/orders/?sort=history">Аяқталған</a>
						</div>
					<? elseif ($sort == 'myself' || $sort == 'myself_yes'): ?>
						<div class="hil_fr1 hil_fr2">
							<a class="hil_fr1c <?=($sort == 'myself'?'hil_fr1c_act':'')?>" href="/orders/?sort=myself">Жаңа</a>
							<a class="hil_fr1c <?=($sort == 'myself_yes'?'hil_fr1c_act':'')?>" href="/orders/?sort=myself_yes">Тапсырылған</a>
						</div>
					<? endif ?>

					<? if ($sort == 'road' || $sort == 'history'): ?>
						<div class="uc_uil2_sel">
							<select name="" id="" class="on_sort_staff" >
								<option data-id="" <?=(@$сourier_id==''?'selected':'')?> value="" >Іздеу: курьер таңдау</option>
								<? $staff = db::query("select * from user_staff where positions_id = 6 and company_id = '$company'"); ?>
								<? while ($staff_d = mysqli_fetch_assoc($staff)): ?>
									<? $staff_user_d = fun::user($staff_d['user_id']); ?>
									<option data-id="<?=$staff_d['user_id']?>" <?=(@$сourier_id==$staff_d['user_id']?'selected':'')?> value="" ><?=$staff_user_d['name']?></option>
								<? endwhile ?>
							</select>
						</div>
					<? endif ?>

					<div class="hil_headc2">
						<div class="hil_headc2s">
							<span>Заказ саны:</span>
							<p class="pp_number"></p>
						</div>
					</div>
					
				</div>

			</div>
		</div>

		<div class="bl_c">

			<div class="uc_u">

				<? if ($orders != ''): ?>
					<? if (mysqli_num_rows($orders) != 0): ?>
						<? while ($buy_d = mysqli_fetch_assoc($orders)): ?>
							<? if ($buy_d['сourier_id']) $сourier_d = fun::user($buy_d['сourier_id']); ?>
							<? if ($buy_d['branch_id']) $branch_d = fun::branch($buy_d['branch_id']); ?>

							<div class="uc_ui">
								<div class="uc_uil2" >
									<div class="uc_uil2_top">
										<div class="uc_uil2_nmb"><?=$buy_d['number']?></div>
										<div class="uc_uil2_date">
											<div class="uc_uil2_date1"><?=@$branch_d['name']?></div>
											<div class=""><?=date("d-m-Y", strtotime($buy_d['ins_dt']))?> ⌛ <?=date("H:i", strtotime($buy_d['ins_dt']))?> <?=($buy_d['preorder_dt']?'| 🔴':'')?>  <?=($buy_d['preorder_dt']?$buy_d['preorder_dt']:'')?></div>
										</div>
										<? if ($buy_d['order_status'] == 1 && $sort == 'myself'): ?>
											<div class="uc_uil2_chek">
												<div class="btn btn_cl btn_44 on_check" data-id="<?=$buy_d['id']?>"><i class="far fa-check"></i></div>
											</div>
										<? endif ?>
									</div>
									<div class="uc_uil2_raz">
										<div class="uc_uil2_trt">
											<div class="uc_uil2_trt1">Атауы</div>
											<div class="uc_uil2_trt2">Саны</div>
											<div class="uc_uil2_trt3">Бағасы</div>
										</div>
										<div class="uc_uil2_trc">

											<? 	
												$cashbox_id = $buy_d['id'];
												$cashboxp = db::query("select * from retail_orders_products where order_id = '$cashbox_id' order by ins_dt asc");
												$number = 0; $total = 0;
											?>
											<? if (mysqli_num_rows($cashboxp) != 0): ?>
												<? while ($sel_d = mysqli_fetch_assoc($cashboxp)): ?>
													<? 
														$number++; 
														$sum = $sel_d['quantity'] * $sel_d['price']; 
														$total = $total + $sum;
														$product_d = product::product($sel_d['product_id']);
													?>
													<div class="uc_uil2_trt">
														<div class="uc_uil2_trt1"><?=$number?>. <?=$product_d['name_ru']?></div>
														<div class="uc_uil2_trt2"><?=$sel_d['quantity']?> шт</div>
														<!-- <div class=""><?=$sel_d['price']?></div> -->
														<div class="uc_uil2_trt3 fr_price"><?=$sum?></div>
													</div>
												<? endwhile ?>
											<? endif ?>
											
											<div class="uc_uil2_trt">
												<div class="uc_uil2_trt1">Доставка</div>
												<div class="uc_uil2_trt3 fr_price"><?=$buy_d['pay_delivery']?></div>
											</div>
											<div class="uc_uil2_trt">
												<div class="uc_uil2_trt1">Предоплата</div>
												<div class="uc_uil2_trt2">-</div>
												<div class="uc_uil2_trt3 fr_price"><?=$buy_d['pay_qr']?></div>
											</div>
										</div>
										<div class="uc_uil2_trb">
											<div class="uc_uil2_trt1">К оплате</div>
											<div class="uc_uil2_trt2"></div>
											<div class="uc_uil2_trt3 fr_price"><?=$buy_d['total'] - $buy_d['pay_qr']?></div>
										</div>
									</div>
									<? if ($buy_d['address']): ?>
										<div class="uc_uil2_raz">
											<div class="uc_uil2_mi">
												<div class="uc_uil2_mi1">Адрес:</div>
												<div class="uc_uil2_mi2"><?=$buy_d['address']?></div>
											</div>
											<? if ($menu_name == 'car' && $sort != 'history'): ?>
												<div class="uc_uil2_mib uc_uil2_mib1">
													<a class="btn btn_cl" href="https://2gis.kz/shymkent/search/<?=$buy_d['address']?>" target="_blank">Картадан ашу</a>
												</div>
											<? endif ?>
											<div class="uc_uil2_mi">
												<div class="uc_uil2_mi1">Оператор:</div>
												<div class="uc_uil2_mi2"><?=(fun::user($buy_d['user_id']))['name']?></div>
											</div>
										</div>
									<? endif ?>

									<? if ($buy_d['phone']): ?>
										<div class="uc_uil2_raz">
											<div class="uc_uil2_mi">
												<div class="uc_uil2_mi1">Номер:</div>
												<div class="uc_uil2_mi2 fr_phone"><?=$buy_d['phone']?></div>
											</div>
											<? if ($menu_name == 'user'): ?>
												<div class="uc_uil2_mib">
													<a class="btn btn_phone" href="tel:8<?=$buy_d['phone']?>">Званок</a>
													<a class="btn btn_whatsapp" href="https://wa.me/7<?=$buy_d['phone']?>" target="_blank">Whatsapp</a>
												</div>
											<? endif ?>
										</div>
									<? endif ?>

									<? if ($menu_name == 'car'): ?>
										<div class="uc_uil2_raz">
											<div class="uc_uil2_mi">
												<div class="uc_uil2_mi1">Курьер:</div>
												<div class="uc_uil2_mi2"><?=($buy_d['сourier_id']?$сourier_d['name']:'Таңдалмаған')?></div>
											</div>
											<? if ($sort != 'history'): ?>
												<div class="uc_uil2_sel">
													<select name="" id="" class="on_staff" data-order-id="<?=$buy_d['id']?>" >
														<option value="" ><?=($buy_d['сourier_id']?'Ауыстыру':'Таңдау')?></option>
														<? if ($buy_d['сourier_id']): ?>
															<option value="" data-id="off">Тазалау</option>
														<? endif ?>
														<? $staff = db::query("select * from user_staff where positions_id = 6 and company_id = '$company'"); ?>
														<? while ($staff_d = mysqli_fetch_assoc($staff)): ?>
															<? $staff_user_d = fun::user($staff_d['user_id']); ?>
															<option value="" data-id="<?=$staff_d['user_id']?>" ><?=$staff_user_d['name']?></option>
														<? endwhile ?>
													</select>
												</div>
											<? endif ?>
										</div>
									<? endif ?>

								</div>
							</div>

							<? 
								$allorder['number'] = $allorder['number'] + 1;
								if ($buy_d['order_status'] != 5 && $buy_d['order_status'] != 6) {
									$allorder['pay_delivery'] = $allorder['pay_delivery'] + $buy_d['pay_delivery'] + 500;
								}
							?>

						<? endwhile ?>
					<? else: ?> <div class="ds_nr"><i class="fal fa-ghost"></i><p>НЕТ</p></div> <? endif ?>
				<? else: ?> div class="ds_nr"><i class="fal fa-ghost"></i><p>НЕТ</p></div> <? endif ?>

			</div>

		</div>

	</div>


	<script>
		$(document).ready(function() {
			$('.pp_number').html('<?=$allorder['number']?> шт');
		})
	</script>

<? include "../block/footer.php"; ?>