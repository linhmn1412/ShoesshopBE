<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        if(request()->isMethod('post')) {
            return [
                'name_shoe' => 'required|string|max:255',
                'id_category' => 'required|exists:category,id_category',
                'id_brand' => 'required|exists:brand,id_brand',
                'id_brand' => 'required|exists:brand,id_brand',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'image_1' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'image_2' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'image_3' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'id_discount' => 'required|exists:discount,id_discount',
                'id_creator' => 'required|exists:staff,id_staff',
                'id_editor' => 'required|exists:staff,id_staff',
                
            ];
        }
    }

    public function message()
    {
        if(request()->isMethod('post')) {
            return [
                'name_shoe.required' => 'Name is required!',
                'image.required' => 'Image is required!',
                'price.required' => 'Price is required!'
            ];
        }
    }
}
