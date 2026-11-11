<div class="form-group">
    <label for="parent_id">Select Category Level</label>
    <select name="parent_id" id="parent_id" class="form-control" style="color: #000">
        <option value="0" @if (isset($category['parent_id']) && $category['parent_id'] == 0) selected @endif>Main Category</option>
        @if (!empty($getCategories))




            @foreach ($getCategories as $parentCategory)
                @php

                @endphp

                <option value="{{ $parentCategory['id'] }}" @if (isset($category['parent_id']) && $category['parent_id'] == $parentCategory['id']) selected @endif>
                    {{ $parentCategory['category_name'] }}</option>




                @if (!empty($parentCategory['subCategories']))
                    @foreach ($parentCategory['subCategories'] as $subcategory)
                        <option value="{{ $subcategory['id'] }}" @if (isset($subcategory['parent_id']) && $subcategory['parent_id'] == $subcategory['id']) selected @endif>
                            &nbsp;&raquo;&nbsp;{{ $subcategory['category_name'] }}</option>
                    @endforeach
                @endif
            @endforeach


        @endif
    </select>
</div>
