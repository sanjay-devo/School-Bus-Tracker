# Image Assets

This folder contains map markers and other images.

## Required Images

### 1. bus-marker.png
- **Purpose**: Bus location marker on map
- **Size**: 40x40 pixels recommended
- **Format**: PNG with transparency
- **Download from**: https://www.flaticon.com/free-icon/school-bus_3448339
- Or create using any icon generator

### 2. user-marker.png
- **Purpose**: User/Parent location marker on map
- **Size**: 30x30 pixels recommended
- **Format**: PNG with transparency
- **Download from**: https://www.flaticon.com/free-icon/user_747376
- Or use default Google Maps marker

### 3. bus-logo.png (optional)
- **Purpose**: Login page logo
- **Size**: 80x80 pixels recommended
- **Format**: PNG with transparency

## Creating Custom Markers

### Method 1: Use Online Icon Generators
- IconFinder: https://www.iconfinder.com/
- Flaticon: https://www.flaticon.com/
- Icons8: https://icons8.com/

### Method 2: Use Canva
1. Go to Canva.com
2. Create custom size (40x40)
3. Add bus icon or emoji 🚌
4. Download as PNG

### Method 3: Use Google Material Icons
1. Visit: https://fonts.google.com/icons
2. Search for "directions_bus"
3. Download PNG

## Fallback

If images are not available, the map will use default markers from Google Maps API. The tracking will still work perfectly.

## SVG Alternative

You can also use inline SVG in the PHP files instead of PNG images:

```javascript
const busIcon = {
    path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
    fillColor: '#4CAF50',
    fillOpacity: 1,
    strokeColor: '#fff',
    strokeWeight: 2,
    rotation: 0,
    scale: 6
};
```

This method doesn't require any image files!
