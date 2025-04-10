<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\ProductRequest;
use Illuminate\Support\Facades\Auth;

class ProductController
{

    /**
     * Handle the request to retrieve a paginated list of products.
     *
     * @param ProductRequest $request The request instance containing input data.
     * @return \Illuminate\Http\JsonResponse A JSON response with the results or an error message.
     * @throws \Exception If an unexpected error occurs during processing.
     */
    public function index(ProductRequest $request)
    {
        try {

            if (!Auth::user())
                return response()->json(['message' => 'You are not authorized to access this resource'], 403);

            // Set default values
            $page = $request->get('page', 1);
            $per_page = $request->get('per_page', 10);
            // Query with filtering
            $query = Product::query();

            // Paginate the results
            $results = $query->paginate($per_page, ['*'], 'page', $page);
            // Check if results are empty
            if ($results->isEmpty())
                return response()->json(['message' => 'No products found'], 404);


            return response()->json([
                'message' => 'Products retrieved successfully',
                'data' => $results
            ], 200);

        } catch (\Exception $e) {
            throw $e;
        }
    }

}
