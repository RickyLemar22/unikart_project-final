# External Products - Search Functionality Guide

## Overview
The external products display now includes comprehensive search capabilities across all product sources (DummyJSON, Fake Store, Platzi).

## Search Features

### 1. **Real-time Search**
- Search by product name or description
- Instantly filters results
- Works across all product sources
- Case-insensitive matching

### 2. **Search Across Sources**
Search results work consistently across:
- **DummyJSON**: Uses native search API for optimal performance
- **Fake Store**: Client-side filtering across all products
- **Platzi**: Client-side filtering for precision results

### 3. **Search Display**
- Shows active search tag with search term
- Displays product count
- Quick clear button to remove search filter
- Maintains search when switching sources

## How Search Works

### URL Parameters
```
/external_products_display.php?source=dummyjson&search=laptop&limit=20&page=1
```

**Query String Parameters:**
- `source`: Product source (dummyjson, fakestore, platzi)
- `search`: Search keyword
- `limit`: Results per page (1-50)
- `page`: Page number for pagination

### Search Examples

```
# Search for laptops on DummyJSON
/external_products_display.php?source=dummyjson&search=laptop

# Search for phones on Fake Store
/external_products_display.php?source=fakestore&search=phone

# Search on Platzi
/external_products_display.php?source=platzi&search=headphones
```

## Frontend Implementation

### Search Form
Located at the top of the products page:
- Input field for search term
- Submit button
- Clear button (only visible when search is active)
- Active search indicator

### Form Behavior
```html
<form method="GET">
    <input type="hidden" name="source" value="dummyjson">
    <input type="text" name="search" placeholder="Search products...">
    <button type="submit">Search</button>
    <a href="?source=dummyjson">Clear</a>
</form>
```

## Backend Search Processing

### DummyJSON Search
```php
// Uses native search API for best performance
$url = "https://dummyjson.com/products/search?q=" . urlencode($search);
```

**Advantages:**
- Native API support
- Server-side filtering
- Optimal performance
- Consistent results

### Fake Store & Platzi Search
```php
// Client-side filtering
$search_lower = strtolower($search);
$products = array_filter($products, function($p) use ($search_lower) {
    return strpos(strtolower($p['title']), $search_lower) !== false ||
           strpos(strtolower($p['description']), $search_lower) !== false;
});
```

## Cache Behavior with Search

### No Caching on Search
When a search is active:
- ❌ Results NOT cached
- ✅ Fresh results on each search
- ✅ Real-time filtering
- ✅ Accurate search results

### Caching on Browse
When browsing without search:
- ✅ Results cached for 1 hour
- ✅ Faster page loads
- ✅ Reduced API calls

## JavaScript Integration

### Search Term Preservation
When switching sources, search term is preserved:
```javascript
function changeSource(source) {
    const searchParam = new URLSearchParams(window.location.search).get('search');
    let url = `external_products_display.php?source=${source}&limit=20&page=1`;
    if (searchParam) {
        url += `&search=${encodeURIComponent(searchParam)}`;
    }
    window.location.href = url;
}
```

### Auto-focus Feature
Search input automatically focuses when there's an active search:
```javascript
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput && searchInput.value) {
        searchInput.focus();
        searchInput.select();
    }
});
```

## Search Results Display

### Product Count
Shows total products found:
- "Found 42 product(s)" - when browsing
- "Searching: 'laptop'" - when actively searching

### Empty Results
If no products match:
- Shows empty state message
- "No products found"
- Suggests different search term

## Advanced Search Tips

### Partial Matching
Search matches partial terms:
- "lap" → finds "laptop", "laptops"
- "phone" → finds "smartphone", "iphone"
- "red" → finds "red lipstick", "reddish"

### Multi-word Search
Search supports phrases:
- "wireless headphones" - searches for both words
- "blue shirt" - finds products containing both terms

### Special Characters
- Automatically handled
- No special character escaping needed
- Safe for SQL injection

## Performance Considerations

### Search Optimization
1. **DummyJSON**: Uses native API (fastest)
2. **Fake Store**: 20 products total (quick filtering)
3. **Platzi**: Up to 100+ products (client-side)

### Search Performance
- DummyJSON: < 500ms
- Fake Store: < 100ms (small dataset)
- Platzi: < 1s (large dataset)

## API Search Parameters

### DummyJSON Search API
```
GET https://dummyjson.com/products/search?q=laptop&limit=20
```

Returns:
- Exact matches
- Partial matches
- Ranked by relevance

### Direct Product Filtering
```php
// Manual search on all products
$url = "https://dummyjson.com/products?limit=100";
$all_products = fetchProducts($url);
$results = filterBySearch($all_products, $search_term);
```

## Testing Search Functionality

### Test Cases
1. **Basic Search**
   - Search term: "laptop"
   - Expected: Products with "laptop" in name/description

2. **Partial Match**
   - Search term: "lap"
   - Expected: Same results as "laptop"

3. **No Results**
   - Search term: "xyzabc"
   - Expected: Empty state message

4. **Special Characters**
   - Search term: "shirt & tie"
   - Expected: Handled gracefully

5. **Source Switching**
   - Search "phone" on DummyJSON
   - Switch to Fake Store
   - Expected: Search term preserved, new results

## Troubleshooting

### Search returning no results
1. Try simpler search term
2. Check if API is responding
3. Verify internet connection
4. Try different source

### Search very slow
1. Use DummyJSON (fastest)
2. Use shorter search terms
3. Check internet speed
4. Try limiting results

### Search not working
1. Check browser console for errors
2. Verify PHP error logs
3. Test API directly
4. Clear browser cache

## Future Enhancements

- [ ] Advanced search filters (price range, ratings)
- [ ] Search history
- [ ] Search suggestions/autocomplete
- [ ] Filter by category while searching
- [ ] Sort search results by relevance
- [ ] Save searches to favorites
