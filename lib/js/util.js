function isEmpty(value) {
    // null or undefined
    if (value == null) return true;
  
    // Strings and arrays
    if (typeof value === 'string' || Array.isArray(value)) {
        return value.length === 0;
    }
  
    // Map or Set
    if (value instanceof Map || value instanceof Set) {
        return value.size === 0;
    }
  
    // Plain object
    if (typeof value === 'object') {
        return Object.keys(value).length === 0;
    }
  
    return false; // numbers, booleans, functions, etc.
  }
  