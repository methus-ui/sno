import 'package:carousel_slider/carousel_slider.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:shimmer_animation/shimmer_animation.dart';
import 'package:sixam_mart/features/item/controllers/item_controller.dart';
import 'package:sixam_mart/features/splash/controllers/splash_controller.dart';
import 'package:sixam_mart/features/item/domain/models/item_model.dart';
import 'package:sixam_mart/features/home/widgets/components/item_that_you_love_card_widget.dart';
import 'package:sixam_mart/helper/price_converter.dart';
import 'package:sixam_mart/util/app_constants.dart';
import 'package:sixam_mart/util/dimensions.dart';
import 'package:sixam_mart/util/styles.dart';
import 'package:sixam_mart/common/widgets/add_favourite_view.dart';
import 'package:sixam_mart/common/widgets/custom_image.dart';
import 'package:sixam_mart/common/widgets/discount_tag.dart';
import 'package:sixam_mart/common/widgets/hover/on_hover.dart';
import 'package:sixam_mart/features/home/widgets/web/widgets/arrow_icon_button.dart';

class WebItemThatYouLoveViewWidget extends StatefulWidget {
  const WebItemThatYouLoveViewWidget({super.key});

  @override
  State<WebItemThatYouLoveViewWidget> createState() => _WebItemThatYouLoveViewWidgetState();
}

class _WebItemThatYouLoveViewWidgetState extends State<WebItemThatYouLoveViewWidget> {
  final CarouselController carouselController = CarouselController();

  String _itemName(int index, List<Item> items) => items[index].name ?? 'item'.tr;
  String _itemImageUrl(int index, List<Item> items) {
    final baseUrl = Get.find<SplashController>().configModel?.baseUrls?.itemImageUrl ?? '';
    final image = items[index].image ?? '';
    return image.isEmpty ? '' : '$baseUrl/$image';
  }

  double _ratingValue(int index, List<Item> items) => items[index].avgRating ?? 0.0;
  int _ratingCount(int index, List<Item> items) => items[index].ratingCount ?? 0;
  double _discountValue(int index, List<Item> items) => items[index].discount ?? 0;

  @override
  Widget build(BuildContext context) {
    bool isShop = Get.find<SplashController>().module != null && Get.find<SplashController>().module!.moduleType.toString() == AppConstants.ecommerce;
    return GetBuilder<ItemController>(builder: (itemController) {
      List<Item>? recommendItems = itemController.recommendedItemList;

      return recommendItems != null ? recommendItems.isNotEmpty ? Stack(children: [
          Column(children: [

            Padding(
              padding: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeDefault),
              child: Text('item_that_you_love'.tr, style: robotoBold.copyWith(fontSize: Dimensions.fontSizeLarge)),
            ),

            !isShop ? CarouselSlider.builder(
              itemCount: recommendItems.length,
              carouselController: carouselController,
              options: CarouselOptions(
                height: 400,
                enlargeCenterPage: true,
                disableCenter: true,
                viewportFraction: .25,
                enlargeFactor: 0.2,
                onPageChanged: (index, reason) {},
              ),
              itemBuilder: (BuildContext context, int index, int realIndex) {
                final item = recommendItems[index];
                return Padding(
                  padding: const EdgeInsets.only(bottom: Dimensions.paddingSizeDefault),
                  child: ItemThatYouLoveCard(item: item),
                );
              },
            ) : SizedBox(
              height: 285,
              child: ListView.builder(
                scrollDirection: Axis.horizontal,
                physics: const BouncingScrollPhysics(),
                padding: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeDefault),
                itemCount: recommendItems.length,
                itemBuilder: (context, index) {
                  final item = recommendItems[index];
                  final itemName = _itemName(index, recommendItems);
                  final imageUrl = _itemImageUrl(index, recommendItems);
                  final rating = _ratingValue(index, recommendItems);
                  final ratingCount = _ratingCount(index, recommendItems);
                  final discount = _discountValue(index, recommendItems);
                  return Padding(
                    padding: EdgeInsets.only(left: index == 0 ? 0 : Dimensions.paddingSizeDefault),
                    child: OnHover(
                      isItem: true,
                      child: InkWell(
                        onTap: () => Get.find<ItemController>().navigateToItemPage(item, context),
                        child: Container(
                          width: 210, height: 285,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(Dimensions.radiusSmall),
                            color: Theme.of(context).cardColor,
                          ),
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            Expanded(
                              child: Stack(children: [
                                Padding(
                                  padding: const EdgeInsets.all(Dimensions.paddingSizeExtraSmall),
                                  child: ClipRRect(
                                    borderRadius: const BorderRadius.all(Radius.circular(Dimensions.radiusSmall)),
                                    child: CustomImage(
                                      image: imageUrl,
                                      fit: BoxFit.cover, width: double.infinity, height: double.infinity,
                                    ),
                                  ),
                                ),

                                AddFavouriteView(
                                  top: 10, right: 10,
                                  item: Item(id: item.id),
                                ),

                                DiscountTag(
                                  discount: Get.find<ItemController>().getDiscount(item),
                                  discountType: Get.find<ItemController>().getDiscountType(item),
                                ),

                                Positioned(
                                  bottom: 0, left: 0, right: 0,
                                  child: Padding(
                                    padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
                                    child: Stack(
                                      clipBehavior: Clip.none,
                                      children: [
                                        Container(
                                          padding: const EdgeInsets.all(Dimensions.paddingSizeSmall),
                                          decoration: BoxDecoration(
                                            borderRadius: const BorderRadius.only(topLeft: Radius.circular(Dimensions.radiusDefault), topRight: Radius.circular(Dimensions.radiusDefault)),
                                            color: Theme.of(context).cardColor,
                                            boxShadow: [BoxShadow(color: Colors.grey.withOpacity(0.2), spreadRadius: 1, blurRadius: 5, offset: const Offset(0, 1.2))],
                                          ),
                                          child: Column(crossAxisAlignment: CrossAxisAlignment.center, mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                                            Text(itemName, style: robotoBold, maxLines: 1, overflow: TextOverflow.ellipsis),

                                            Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                                              Icon(Icons.star, size: 15, color: Theme.of(context).primaryColor),
                                              const SizedBox(width: Dimensions.paddingSizeExtraSmall),
                                              Text(rating.toStringAsFixed(1), style: robotoRegular),
                                              const SizedBox(width: Dimensions.paddingSizeExtraSmall),
                                              Text("($ratingCount)", style: robotoRegular.copyWith(fontSize: Dimensions.fontSizeSmall, color: Theme.of(context).disabledColor)),
                                            ]),

                                            Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                                              discount > 0 ? Flexible(child: Text(
                                                  PriceConverter.convertPrice(
                                                    Get.find<ItemController>().getStartingPrice(item),
                                                  ),
                                                  style: robotoRegular.copyWith(
                                                    fontSize: Dimensions.fontSizeExtraSmall, color: Theme.of(context).disabledColor, decoration: TextDecoration.lineThrough,
                                                  ))) : const SizedBox(),
                                              SizedBox(width: discount > 0 ? Dimensions.paddingSizeExtraSmall : 0),

                                              Text(
                                                PriceConverter.convertPrice(
                                                  Get.find<ItemController>().getStartingPrice(item),
                                                  discount: item.discount,
                                                  discountType: item.discountType,
                                                ),
                                                style: robotoMedium, textDirection: TextDirection.ltr,
                                              ),
                                            ]),
                                          ]),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              ]),
                            ),
                          ]),
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),
          ]),

        Positioned(
          top: 220, right: 0,
          child: ArrowIconButton(
            onTap: () => carouselController.nextPage(),
          ),
        ),

        Positioned(
          top: 220, left: 0,
          child: ArrowIconButton(
            onTap: () => carouselController.previousPage(),
            isRight: false,
          ),
        ),

      ]) : const SizedBox() : WebItemThatYouLoveShimmerView(itemController: itemController);
    });
  }
}

class WebItemThatYouLoveForShop extends StatefulWidget {
  const WebItemThatYouLoveForShop({super.key});

  @override
  State<WebItemThatYouLoveForShop> createState() => _WebItemThatYouLoveForShopState();
}

class _WebItemThatYouLoveForShopState extends State<WebItemThatYouLoveForShop> {
  ScrollController scrollController = ScrollController();
  bool showBackButton = false;
  bool showForwardButton = false;
  bool isFirstTime = true;

  String _itemName(int index, List<Item> items) => items[index].name ?? 'item'.tr;
  String _itemImageUrl(int index, List<Item> items) {
    final baseUrl = Get.find<SplashController>().configModel?.baseUrls?.itemImageUrl ?? '';
    final image = items[index].image ?? '';
    return image.isEmpty ? '' : '$baseUrl/$image';
  }

  double _ratingValue(int index, List<Item> items) => items[index].avgRating ?? 0.0;
  int _ratingCount(int index, List<Item> items) => items[index].ratingCount ?? 0;
  double _discountValue(int index, List<Item> items) => items[index].discount ?? 0;

  @override
  void initState() {
    scrollController.addListener(_checkScrollPosition);
    super.initState();
  }

  @override
  void dispose() {
    scrollController.dispose();
    super.dispose();
  }

  void _checkScrollPosition() {
    setState(() {
      if (scrollController.position.pixels <= 0) {
        showBackButton = false;
      } else {
        showBackButton = true;
      }

      if (scrollController.position.pixels >= scrollController.position.maxScrollExtent) {
        showForwardButton = false;
      } else {
        showForwardButton = true;
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<ItemController>(builder: (itemController) {
      List<Item>? recommendItems = itemController.recommendedItemList;

      if (recommendItems != null && recommendItems.length > 5 && isFirstTime) {
        showForwardButton = true;
        isFirstTime = false;
      }

      return recommendItems != null ? recommendItems.isNotEmpty
          ? Stack(children: [
              Column(children: [
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeDefault),
                  child: Text('item_that_you_love'.tr, style: robotoBold.copyWith(fontSize: Dimensions.fontSizeLarge)),
                ),
                Container(
                  color: Theme.of(context).cardColor,
                  height: 285, width: Get.width,
                  child: ListView.builder(
                    controller: scrollController,
                    scrollDirection: Axis.horizontal,
                    physics: const BouncingScrollPhysics(),
                    padding: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeDefault),
                    itemCount: recommendItems.length,
                    itemBuilder: (context, index) {
                      final item = recommendItems[index];
                      final itemName = _itemName(index, recommendItems);
                      final imageUrl = _itemImageUrl(index, recommendItems);
                      final rating = _ratingValue(index, recommendItems);
                      final ratingCount = _ratingCount(index, recommendItems);
                      final discount = _discountValue(index, recommendItems);
                      return Padding(
                        padding: EdgeInsets.only(left: index == 0 ? 0 : Dimensions.paddingSizeDefault),
                        child: OnHover(
                          isItem: true,
                          child: InkWell(
                            onTap: () => Get.find<ItemController>().navigateToItemPage(item, context),
                            child: Container(
                              width: 210, height: 285,
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(Dimensions.radiusSmall),
                                color: Theme.of(context).cardColor,
                              ),
                              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                                Expanded(
                                  child: Stack(children: [
                                    Padding(
                                      padding: const EdgeInsets.all(Dimensions.paddingSizeExtraSmall),
                                      child: ClipRRect(
                                        borderRadius: const BorderRadius.all(Radius.circular(Dimensions.radiusSmall)),
                                        child: CustomImage(
                                          image: imageUrl,
                                          fit: BoxFit.cover, width: double.infinity, height: double.infinity,
                                        ),
                                      ),
                                    ),

                                    AddFavouriteView(
                                      top: 10, right: 10,
                                      item: Item(id: item.id),
                                    ),

                                    DiscountTag(
                                      discount: Get.find<ItemController>().getDiscount(item),
                                      discountType: Get.find<ItemController>().getDiscountType(item),
                                    ),

                                    Positioned(
                                      bottom: 0, left: 0, right: 0,
                                      child: Padding(
                                        padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
                                        child: Stack(
                                          clipBehavior: Clip.none,
                                          children: [
                                            Container(
                                              padding: const EdgeInsets.all(Dimensions.paddingSizeSmall),
                                              decoration: BoxDecoration(
                                                borderRadius: const BorderRadius.only(topLeft: Radius.circular(Dimensions.radiusDefault), topRight: Radius.circular(Dimensions.radiusDefault)),
                                                color: Theme.of(context).cardColor,
                                                boxShadow: [BoxShadow(color: Colors.grey.withOpacity(0.2), spreadRadius: 1, blurRadius: 5, offset: const Offset(0, 1.2))],
                                              ),
                                              child: Column(crossAxisAlignment: CrossAxisAlignment.center, mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                                                Text(itemName, style: robotoBold, maxLines: 1, overflow: TextOverflow.ellipsis),

                                                Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                                                  Icon(Icons.star, size: 15, color: Theme.of(context).primaryColor),
                                                  const SizedBox(width: Dimensions.paddingSizeExtraSmall),
                                                  Text(rating.toStringAsFixed(1), style: robotoRegular),
                                                  const SizedBox(width: Dimensions.paddingSizeExtraSmall),
                                                  Text("($ratingCount)", style: robotoRegular.copyWith(fontSize: Dimensions.fontSizeSmall, color: Theme.of(context).disabledColor)),
                                                ]),

                                                Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                                                  discount > 0 ? Flexible(child: Text(
                                                      PriceConverter.convertPrice(
                                                        Get.find<ItemController>().getStartingPrice(item),
                                                      ),
                                                      style: robotoRegular.copyWith(
                                                        fontSize: Dimensions.fontSizeExtraSmall, color: Theme.of(context).disabledColor, decoration: TextDecoration.lineThrough,
                                                      ))) : const SizedBox(),
                                                  SizedBox(width: discount > 0 ? Dimensions.paddingSizeExtraSmall : 0),

                                                  Text(
                                                    PriceConverter.convertPrice(
                                                      Get.find<ItemController>().getStartingPrice(item),
                                                      discount: item.discount,
                                                      discountType: item.discountType,
                                                    ),
                                                    style: robotoMedium, textDirection: TextDirection.ltr,
                                                  ),
                                                ]),
                                              ]),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ),
                                  ]),
                                ),
                              ]),
                            ),
                          ),
                        ),
                      );
                    },
                  ),
                ),
              ]),

              if (showBackButton)
                Positioned(
                  top: 200, left: 0,
                  child: ArrowIconButton(
                    isRight: false,
                    onTap: () => scrollController.animateTo(scrollController.offset - Dimensions.webMaxWidth,
                        duration: const Duration(milliseconds: 500), curve: Curves.easeInOut),
                  ),
                ),

              if (showForwardButton)
                Positioned(
                  top: 200, right: 0,
                  child: ArrowIconButton(
                    onTap: () => scrollController.animateTo(scrollController.offset + Dimensions.webMaxWidth,
                        duration: const Duration(milliseconds: 500), curve: Curves.easeInOut),
                  ),
                ),
            ]) : const SizedBox() : const WebItemThatYouLoveForShopShimmer();
    });
  }
}

class WebItemThatYouLoveForShopShimmer extends StatelessWidget {
  const WebItemThatYouLoveForShopShimmer({super.key});

  @override
  Widget build(BuildContext context) {
    return Stack(children: [
      Column(children: [
        Padding(
          padding: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeDefault),
          child: Text('item_that_you_love'.tr, style: robotoBold.copyWith(fontSize: Dimensions.fontSizeLarge)),
        ),
        Shimmer(
          enabled: true,
          duration: const Duration(seconds: 2),
          child: Container(
            color: Theme.of(context).cardColor,
            height: 285, width: Get.width,
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              physics: const BouncingScrollPhysics(),
              padding: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeDefault),
              itemCount: 10,
              itemBuilder: (context, index) {
                return Padding(
                  padding: EdgeInsets.only(left: index == 0 ? 0 : Dimensions.paddingSizeDefault),
                  child: Container(
                    width: 210, height: 285,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(Dimensions.radiusSmall),
                      color: Theme.of(context).cardColor,
                    ),
                  ),
                );
              },
            ),
          ),
        ),
      ]),
    ]);
  }
}

class WebItemThatYouLoveShimmerView extends StatelessWidget {
  final ItemController itemController;
  const WebItemThatYouLoveShimmerView({super.key, required this.itemController});

  @override
  Widget build(BuildContext context) {
    return const SizedBox();
  }
}
