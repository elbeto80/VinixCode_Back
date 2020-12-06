<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\PetModels;
use App\Models\CategoryModel;
use App\Models\TagModel;

class HomeController extends Controller
{
    public function paramsHome(Request $request) {
        try {
            $params = [];

            $petsTotal       = PetModels::count();
            $categoriesTotal = CategoryModel::count();
            $tagsTotal 		 = TagModel::count();

            $params['petsTotal'] 	   = $petsTotal;
            $params['categoriesTotal'] = $categoriesTotal;
            $params['tagsTotal']       = $tagsTotal;

            return ['error' => 0,'info'=> $params];

        } catch(Exception $e) {
            error_log($e,0);
            return ['error' => 1,'info'=> (string)$e];
        }
    }
}
