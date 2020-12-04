<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\CategoryModel;

use \Validator;
use \Exception;
use \DB;

class CategoryController extends Controller
{
    public function saveCategory(Request $request) {
        try {
            $values = $request->input();

            // VALIDACIONES DE CAMPOS OBLIGATORIOS POR LARAVEL
            $rules = [
                'categoryName' => 'required',
            ];
            $input = [
                'categoryName'   => $values['categoryName'],
            ];
            $erros = [
                'categoryName.required' => 'Nombre de categoría abligatorio'
            ];

            $validate = Validator::make($input, $rules, $erros);
            if($validate->fails()){
                $messages = $validate->messages()->all();
                return ['error' => 1, 'info' => $messages];
            }

            // SI TRAE idBoleta MODIFICA SINO CREA NUEVO
            if( !$values['idCategory'] ){
                $category = new CategoryModel();
            } else {
                $category = CategoryModel::findOrFail($values['idCategory']);
            }
            $category->name = $values['categoryName'];

            if(!$category->save()){
                DB::rollback();
                return ['error' => 1, 'info' => 'No ha sido posible Guardar categoría.'];
            }

            return ['error' => 0,'info' => 'Categoria guardada correctamente'];

        } catch(Exception $e) {
            error_log($e,0);
            return ['error' => 1,'info'=> (string)$e];
        }
    }


    public function getCategories(Request $request) {
        try {
            $values = $request->input();

            $category = new CategoryModel();
            $CA       = $category->getTable();

            $categories = $category->select("$CA.id", "$CA.name")
            ->where(function($query) use ($values, $CA) {
                if(!empty($values['inputSearch'])) {
                    $query->orWhere("$CA.name", 'like', "%".$values['inputSearch']."%");
                }
            })
            ->orderBy("$CA.name")
            ->get();

            $response = [
                'categories'=> $categories
            ];

            return ['error' => 0, 'info' => $response];
        } catch(Exception $e) {
            error_log($e,0);
            return ['error' => 1,'info'=> (string)$e];
        }
    }

    public function deleteCategory(Request $request) {
        try {
            $category = CategoryModel::findOrFail($request->idCategory);
            if(!$category->delete()){
                DB::rollback();
                return ['error' => 1, 'info' => 'No ha sido posible eliminar categoría.'];
            }

            return ['error' => 0, 'info' => 'Categoría borrada correctamente'];
        } catch(Exception $e) {
            error_log($e,0);
            return ['error' => 1,'info'=> (string)$e];
        }
    }

}
