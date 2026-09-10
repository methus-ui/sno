import 'package:sixam_mart/helper/responsive_helper.dart';
import 'package:sixam_mart/util/dimensions.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixam_mart/util/styles.dart';

void showCustomSnackBar(String? message, {bool isError = true, bool getXSnackBar = false}) {
  if(message != null && message.isNotEmpty) {
    if(getXSnackBar) {
      Get.showSnackbar(GetSnackBar(
        backgroundColor: isError ? Colors.red : Colors.green,
        message: message,
        maxWidth: 500,
        duration: const Duration(seconds: 3),
        snackStyle: SnackStyle.FLOATING,
        margin: const EdgeInsets.only(left: Dimensions.paddingSizeSmall, right:  Dimensions.paddingSizeSmall, bottom:  100),
        borderRadius: Dimensions.radiusSmall,
        isDismissible: true,
        dismissDirection: DismissDirection.horizontal,
      ));
    }else {
      // Get.context can still be null early during startup (e.g. an API error
      // arriving before the first frame). Using `Get.context!` there throws
      // "Unexpected null value" — fall back to a GetX snackbar instead.
      final BuildContext? context = Get.context;
      if (context != null) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          dismissDirection: DismissDirection.horizontal,
          margin: EdgeInsets.only(
            right: ResponsiveHelper.isDesktop(context) ? context.width*0.7 : Dimensions.paddingSizeSmall,
            top: Dimensions.paddingSizeSmall, bottom: Dimensions.paddingSizeSmall, left: Dimensions.paddingSizeSmall,
          ),
          duration: const Duration(seconds: 3),
          backgroundColor: isError ? Colors.red : Colors.green,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.radiusSmall)),
          content: Text(message, style: robotoMedium.copyWith(color: Colors.white)),
        ));
      } else {
        Get.showSnackbar(GetSnackBar(
          backgroundColor: isError ? Colors.red : Colors.green,
          message: message,
          maxWidth: 500,
          duration: const Duration(seconds: 3),
          snackStyle: SnackStyle.FLOATING,
          margin: const EdgeInsets.only(left: Dimensions.paddingSizeSmall, right:  Dimensions.paddingSizeSmall, bottom:  100),
          borderRadius: Dimensions.radiusSmall,
          isDismissible: true,
          dismissDirection: DismissDirection.horizontal,
        ));
      }
    }
  }
}