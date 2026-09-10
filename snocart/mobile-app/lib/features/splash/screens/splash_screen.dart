import 'dart:async';

import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:sixam_mart/features/auth/controllers/auth_controller.dart';
import 'package:sixam_mart/features/cart/controllers/cart_controller.dart';
import 'package:sixam_mart/features/location/controllers/location_controller.dart';
import 'package:sixam_mart/features/splash/controllers/splash_controller.dart';
import 'package:sixam_mart/features/favourite/controllers/favourite_controller.dart';
import 'package:sixam_mart/features/notification/domain/models/notification_body_model.dart';
import 'package:sixam_mart/helper/address_helper.dart';
import 'package:sixam_mart/helper/auth_helper.dart';
import 'package:sixam_mart/helper/route_helper.dart';
import 'package:sixam_mart/util/app_constants.dart';
import 'package:sixam_mart/util/dimensions.dart';
import 'package:sixam_mart/util/images.dart';
import 'package:sixam_mart/util/styles.dart';
import 'package:sixam_mart/common/widgets/no_internet_screen.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

class SplashScreen extends StatefulWidget {
  final NotificationBodyModel? body;
  const SplashScreen({super.key, required this.body});

  @override
  SplashScreenState createState() => SplashScreenState();
}

class SplashScreenState extends State<SplashScreen> {
  final GlobalKey<ScaffoldState> _globalKey = GlobalKey();
  late StreamSubscription<ConnectivityResult> _onConnectivityChanged;

  @override
  void initState() {
    super.initState();

    bool firstTime = true;
    _onConnectivityChanged = Connectivity().onConnectivityChanged.listen((ConnectivityResult result) {
      if(!firstTime) {
        bool isNotConnected = result != ConnectivityResult.wifi && result != ConnectivityResult.mobile;
        isNotConnected ? const SizedBox() : ScaffoldMessenger.of(context).hideCurrentSnackBar();
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          backgroundColor: isNotConnected ? Colors.red : Colors.green,
          duration: Duration(seconds: isNotConnected ? 6000 : 3),
          content: Text(
            isNotConnected ? 'no_connection'.tr : 'connected'.tr,
            textAlign: TextAlign.center,
          ),
        ));
        if(!isNotConnected) {
          _route();
        }
      }
      firstTime = false;
    });

    Get.find<SplashController>().initSharedData();
    if((AuthHelper.getGuestId().isNotEmpty || AuthHelper.isLoggedIn()) && Get.find<SplashController>().cacheModule != null) {
      Get.find<CartController>().getCartDataOnline();
    }
    _route();

  }

  @override
  void dispose() {
    super.dispose();

    _onConnectivityChanged.cancel();
  }

  void _route() {
    Get.find<SplashController>().getConfigData().then((isSuccess) {
      if(isSuccess) {
        Timer(const Duration(seconds: 1), () async {
          final config = Get.find<SplashController>().configModel;
          double minimumVersion = 0;
          if(GetPlatform.isAndroid) {
            minimumVersion = config?.appMinimumVersionAndroid ?? 0;
          }else if(GetPlatform.isIOS) {
            minimumVersion = config?.appMinimumVersionIos ?? 0;
          }
          final bool isMaintenanceMode = config?.maintenanceMode ?? false;
          if((minimumVersion > 0 && AppConstants.appVersion < minimumVersion) || isMaintenanceMode) {
            Get.offNamed(RouteHelper.getUpdateRoute(AppConstants.appVersion < minimumVersion));
          }else {
            if(widget.body != null) {
              if (widget.body!.notificationType == NotificationType.order) {
                Get.offNamed(RouteHelper.getOrderDetailsRoute(widget.body!.orderId, fromNotification: true));
              }else if(widget.body!.notificationType == NotificationType.general){
                Get.offNamed(RouteHelper.getNotificationRoute(fromNotification: true));
              }else {
                Get.offNamed(RouteHelper.getChatRoute(notificationBody: widget.body, conversationID: widget.body!.conversationId, fromNotification: true));
              }
            }else {
              if (AuthHelper.isLoggedIn()) {
                Get.find<AuthController>().updateToken();
                if (AddressHelper.getUserAddressFromSharedPref() != null) {
                  if(Get.find<SplashController>().module != null) {
                    await Get.find<FavouriteController>().getFavouriteList();
                  }
                  Get.offNamed(RouteHelper.getInitialRoute(fromSplash: true));
                } else {
                  Get.find<LocationController>().navigateToLocationScreen('splash', offNamed: true);
                }
              } else {
                if (Get.find<SplashController>().showIntro() ?? false) {
                  if(AppConstants.languages.length > 1) {
                    Get.offNamed(RouteHelper.getLanguageRoute('splash'));
                  }else {
                    Get.offNamed(RouteHelper.getOnBoardingRoute());
                  }
                } else {
                  if(AuthHelper.isGuestLoggedIn()) {
                    if (AddressHelper.getUserAddressFromSharedPref() != null) {
                      Get.offNamed(RouteHelper.getInitialRoute(fromSplash: true));
                    } else {
                      Get.find<LocationController>().navigateToLocationScreen('splash', offNamed: true);
                    }
                  } else {
                    Get.offNamed(RouteHelper.getSignInRoute(RouteHelper.splash));
                  }
                }
              }
            }
          }
        });
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    Get.find<SplashController>().initSharedData();
    if(AddressHelper.getUserAddressFromSharedPref() != null && AddressHelper.getUserAddressFromSharedPref()!.zoneIds == null) {
      Get.find<AuthController>().clearSharedAddress();
    }

    return Scaffold(
      key: _globalKey,
      body: GetBuilder<SplashController>(builder: (splashController) {
        if(!splashController.hasConnection) {
          return NoInternetScreen(child: SplashScreen(body: widget.body));
        }
        if(splashController.configError != null) {
          // /api/v1/config failed (e.g. HTTP 500): show the server's error
          // message with a retry button instead of hanging on the logo forever.
          return _ConfigErrorView(message: splashController.configError, onRetry: _route);
        }
        return Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Image.asset(Images.logo, width: 200),
              const SizedBox(height: Dimensions.paddingSizeSmall),
              // Text(AppConstants.APP_NAME, style: robotoMedium.copyWith(fontSize: 25)),
            ],
          ),
        );
      }),
    );
  }
}

class _ConfigErrorView extends StatelessWidget {
  final String? message;
  final VoidCallback onRetry;

  const _ConfigErrorView({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(Dimensions.paddingSizeLarge),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.error_outline, size: 80, color: Theme.of(context).colorScheme.error),
            const SizedBox(height: Dimensions.paddingSizeLarge),
            Text(
              'oops'.tr,
              style: robotoBold.copyWith(fontSize: 30, color: Theme.of(context).textTheme.bodyLarge?.color),
            ),
            const SizedBox(height: Dimensions.paddingSizeSmall),
            Text(
              message ?? '',
              textAlign: TextAlign.center,
              style: robotoRegular.copyWith(color: Theme.of(context).hintColor),
            ),
            const SizedBox(height: 40),
            InkWell(
              onTap: onRetry,
              child: Container(
                width: 150,
                padding: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeSmall),
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(Dimensions.radiusSmall),
                  color: Theme.of(context).primaryColor,
                ),
                child: Text('retry'.tr, style: robotoMedium.copyWith(color: Colors.white)),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
