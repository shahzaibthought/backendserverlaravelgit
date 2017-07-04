<?php 
namespace App\Http\Controllers;

use App\Models\Shop;
use App\Http\Controllers\controller;
use Illuminate\Http\Request;
use Validator, Input, Redirect ;
use DB;
use Config;
use Schema;

class ShopController extends Controller {

	public function fetchShops(Request $request){
		$shops 	=	DB::table('shops')->get();
		return array(
            'success' => true,
            'data'   => $shops
        ); 
	}

	public function createShop(Request $request){
		$id 			=	DB::table('shops')->max('id');
		if(empty($id)){
			$id 		=	 0;
		}
		$shop_name 		=	$request->shop_name;
		$db_name 		=	str_replace(' ','_',strtolower($shop_name)).'_'.time().'_'.$id;
		$shop_insert_id	=	DB::table('shops')->insertGetId(['shop_name'=>$shop_name,'db_name'=>$db_name]);	
		
		// CREATE NEW DATABASE AND TABLE IN IT ON THE FLY
		// DATABASE CREATION
		$sqlCreateDb 	=	'create database '.$db_name;
		DB::statement($sqlCreateDb);
		// SWITCHING DATABASE TO NEWLY CREATED DATABASE
		$this->databaseConfigAlteration($db_name,false);
		// TABLE
		DB::statement("CREATE TABLE products ( id int(11) AUTO_INCREMENT PRIMARY KEY, category mediumtext null default NULL,
			product mediumtext null default NULL, discount int(11) default 0, price int(11) default 0 )");
		// CREATE NEW DATABASE AND TABLE IN IT ON THE FLY COMPLETED
		
		return array(
				'success'=>true,
				'db_name'=>$db_name,
				'shop_id'=>$shop_insert_id,
				'shop_name'=>$shop_name
		);
	}

	public function fetchProducts(Request $request){
		$shop_id 		=	$request->shopId;
		$shop 			=	$this->selectSingleShop($shop_id);
		if(count($shop)==0){
			return array(
				'success'=>false,
				'error'=>'No store found with Store ID : #'.$requestData['shop_id']
			);	
		}
		else{
			$this->shopRequestsIncreament($shop_id,$shop[0]->requests);
			$shop_db 		=	$shop[0]->db_name;
			$shop_name 		=	$shop[0]->shop_name;
			// Changing database connection to shop's database
			$this->databaseConfigAlteration($shop_db,false);
			$products 		=	DB::table('products')->get();
			return array(
	            'success' => true,
	            'data'   => $products,
	            'shop_name'=> $shop_name
	        );
		}
	}

	public function addProduct(Request $request){	
		$requestData 	=	$request->all();
		$shop 			=	$this->selectSingleShop($requestData['shop_id']);
		if(count($shop)==0){
			return array(
				'success'=>false,
				'error'=>'No store found with Store ID : #'.$requestData['shop_id']
			);	
		}
		else{
			// Mean store has been found with store id
			$this->shopRequestsIncreament($requestData['shop_id'],$shop[0]->requests);
			$shop_db 			=	$shop[0]->db_name;
			// Changing database connection to shop's database
			$this->databaseConfigAlteration($shop_db,false);
			$price 				=	$this->calcOfferedPrice($requestData);
			$product_insert_id	=	DB::table('products')->insertGetId(['category'=>$requestData['category_name'],'product'=>$requestData['product_name'], 'discount'=>$requestData['product_discount'] , 'price'=>$price]);
			return array(
				'success'=>true,
				'product_id'=>$product_insert_id,
			);
		}
	}

	public function modifyProduct(Request $request){
		$requestData 	=	$request->all();
		$shop 			=	$this->selectSingleShop($requestData['shop_id']);
		if(count($shop)==0){
			return array(
				'success'=>false,
				'error'=>'No store found with Store ID : #'.$requestData['shop_id']
			);	
		}
		else{
			// Mean store has been found with store id
			$this->shopRequestsIncreament($requestData['shop_id'],$shop[0]->requests);
			$shop_db 		=	$shop[0]->db_name;
			// Changing database connection to shop's database
			$this->databaseConfigAlteration($shop_db,false);
			$product 		=	DB::table('products')->where('id','=',$requestData['product_id'])->get();
			if(count($product)==0){
				return array(
					'success'=>false,
					'error'=>'No product found with Product ID : #'.$requestData['product_id']
				);	
			}
			else{
				// Update process started from here
				$product_update_type		=	$requestData['product_update_type'];
				if($product_update_type=='put'){
					$price 					=	'';
					if($requestData['product_price']!=''){
						$price 				=	$this->calcOfferedPrice($requestData);
					}
					DB::table('products')->where('id','=',$requestData['product_id'])->update(['category'=>$requestData['category_name'],'product'=>$requestData['product_name'],'discount'=>$requestData['product_discount'],'price'=>$price]);
				}
				else{
					if($requestData['category_name']!=''){
						DB::table('products')->where('id','=',$requestData['product_id'])->update(['category'=>$requestData['category_name']]);
					}
					if($requestData['product_name']!=''){
						DB::table('products')->where('id','=',$requestData['product_id'])->update(['product'=>$requestData['product_name']]);
					}
					if($requestData['product_price']!=''){
						$price 				=	$this->calcOfferedPrice($requestData);
						DB::table('products')->where('id','=',$requestData['product_id'])->update(['discount'=>$requestData['product_discount'],'price'=>$price]);
					}
				}
				return array(
					'success'=>true,
					'product_id'=>$requestData['product_id']
				);	
			}
		}
	}

	public function deleteProduct(Request $request){
		$requestData 	=	$request->all();
		$shop 			=	$this->selectSingleShop($requestData['shop_id']);
		if(count($shop)==0){
			return array(
				'success'=>false,
				'error'=>'No store found with Store ID : #'.$requestData['shop_id']
			);	
		}
		else{
			// Mean store has been found with store id
			$this->shopRequestsIncreament($requestData['shop_id'],$shop[0]->requests);
			$shop_db 		=	$shop[0]->db_name;
			$product_id 	=	$requestData['product_id'];
			// Changing database connection to shop's database
			$this->databaseConfigAlteration($shop_db,false);
			DB::table('products')->where('id','=',$product_id)->delete();
			return array(
				'success'=>true
			);
		}
	}

	// Utility Functions
	public function databaseConfigAlteration($database,$setDefault){
		if($setDefault){
			DB::setDefaultConnection('mysql');	
		}
		else{	
			Config::set('database.connections.shopconnector', array(
		        'driver'    => 'mysql',
		        'host'      => env('DB_HOST','localhost'),
		        'database'  => $database,
		        'username'  => env("DB_USERNAME", "root"),
		        'password'  => env("DB_PASSWORD", ""),
		        'charset'   => 'utf8',
		        'collation' => 'utf8_unicode_ci',
		        'prefix'    => '',
		    ));
			DB::setDefaultConnection('shopconnector');
		}
	}

	public function calcOfferedPrice($requestData){
		$discountPer 		=	$requestData['product_discount']; // Discount Percentage
		if($discountPer=='' || empty($discountPer)){
			$discountPer 	=	0;
		}
		$actualPrice 		=	$requestData['product_price']; // Actual Price
		$discountedAmount 	=	round($actualPrice*($discountPer/100),2);
		$price 				=	$actualPrice-$discountedAmount;
		return $price;
	}

	public function selectSingleShop($shop_id){
		$shop 			=	DB::table('shops')->where('id','=',$shop_id)->get();
		return $shop;
	}

	public function shopRequestsIncreament($shop_id,$count){
		$count+=1;
		DB::table('shops')->where('id','=',$shop_id)->update(['requests'=>$count]);
	}
}