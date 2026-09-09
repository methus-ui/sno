/// Builds a safe image url from a [base] url and a file [path].
/// Returns null when the url cannot be built, so callers can show a placeholder.
String? buildImageUrl(String? base, String? path) {
  if (base == null || base.isEmpty || base == 'null') return null;
  if (path == null || path.isEmpty || path == 'null') return null;
  return '$base/$path';
}
