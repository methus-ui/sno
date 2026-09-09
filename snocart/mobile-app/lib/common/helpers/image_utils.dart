String? buildImageUrl(String? base, String? path) {
  if (base == null || base.isEmpty) return null;
  if (path == null || path.isEmpty) return base;
  return '\$base/\$path';
}
