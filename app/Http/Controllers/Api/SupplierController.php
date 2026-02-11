<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{

   public function __invoke(Request $request)
   {
       $validator = Validator::make($request->all(),[
          'name' => 'required | string | max:255',
          'description' => 'nullable|string'

       ]);

       if($validator->fails()){
        return response()->json([
            'errors'=>$validator->errors()
        ],422);
       }

       $supplier = new Supplier();
       $supplier->name = $request->name;
       $supplier->description = $request->description;

       $supplier->save();


       return response()->json([
        'mes'=>"supplier created"
       ],201);
   }

}