<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
	protected $table 		= 'shops';
	protected $primaryKey 	= 'id';

	public function __construct() {
		parent::__construct();
	}

	 
}