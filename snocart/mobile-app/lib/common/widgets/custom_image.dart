import 'package:cached_network_image/cached_network_image.dart';
import 'package:sixam_mart/util/images.dart';
import 'package:flutter/cupertino.dart';

class CustomImage extends StatelessWidget {
  final String? image;
  final double? height;
  final double? width;
  final BoxFit? fit;
  final bool isNotification;
  final String placeholder;
  const CustomImage({super.key, required this.image, this.height, this.width, this.fit = BoxFit.cover, this.isNotification = false, this.placeholder = ''});

  /// Returns true when the url is unusable and the placeholder must be shown.
  /// This catches backend nulls interpolated into strings ('.../null', 'null').
  static bool isEmptyOrNullUrl(String? url) {
    if (url == null) return true;
    final String u = url.trim();
    if (u.isEmpty) return true;
    if (u == 'null') return true;
    if (u.endsWith('/null')) return true;
    return false;
  }

  @override
  Widget build(BuildContext context) {
    if (isEmptyOrNullUrl(image)) {
      return Image.asset(placeholder.isNotEmpty ? placeholder : isNotification ? Images.notificationPlaceholder : Images.placeholder, height: height, width: width, fit: fit);
    }
    return CachedNetworkImage(
      imageUrl: image!, height: height, width: width, fit: fit,
      placeholder: (context, url) => Image.asset(placeholder.isNotEmpty ? placeholder : isNotification ? Images.notificationPlaceholder : Images.placeholder, height: height, width: width, fit: fit),
      errorWidget: (context, url, error) => Image.asset(placeholder.isNotEmpty ? placeholder : isNotification ? Images.notificationPlaceholder : Images.placeholder, height: height, width: width, fit: fit),
    );
  }
}
