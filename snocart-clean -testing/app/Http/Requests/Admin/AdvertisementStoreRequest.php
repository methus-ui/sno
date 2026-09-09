<?php

namespace App\Http\Requests\Admin;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use App\CentralLogics\Helpers;
class AdvertisementStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules()
    {
        return [
            'store_id' => 'required',
            'title.*' => 'max:255',
            'description.*' => 'nullable|max:65000',
            'dates' => 'required',
            'advertisement_type' => 'required|in:video_promotion,store_promotion',
            'cover_image' => 'required_if:advertisement_type,store_promotion|image|mimes:jpg,png,jpeg,webp|max:2048',
            'profile_image' => 'required_if:advertisement_type,store_promotion|image|mimes:jpg,png,jpeg,webp|max:2048',

            // Video attachment is only required if video_source is 'upload' (not Instagram)
            'video_attachment' => 'required_if:video_source,upload|file|mimes:mp4,mkv,webm|max:5120',

            // Instagram reel URL is required if video_source is instagram_reel or instagram_url
            'instagram_reel_url' => 'required_if:video_source,instagram_reel|url|max:500',
            'instagram_manual_url' => 'required_if:video_source,instagram_url|url|max:500',

            'title.0' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'store_id.required' => translate('messages.Please_select_a_store'),
            'video_attachment.required_if' => translate('messages.Please_upload_a_video_file'),
            'instagram_reel_url.required_if' => translate('messages.Please_select_an_Instagram_reel'),
            'instagram_manual_url.required_if' => translate('messages.Please_enter_an_Instagram_reel_URL'),
            'instagram_manual_url.url' => translate('messages.Please_enter_a_valid_Instagram_URL'),
            'cover_image.required_if' => translate('Your_cover_image_is_missing'),
            'profile_image.required_if' => translate('Your_profile_image_is_missing'),
            'title.0.required'=>translate('default_title_is_required'),
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $dateRange = $this->dates;
            list($startDate, $endDate) = explode(' - ', $dateRange);
            $startDate = Carbon::createFromFormat('m/d/Y', trim($startDate))->startOfDay();
            $endDate = Carbon::createFromFormat('m/d/Y', trim($endDate))->endOfDay();

            if ($startDate < Carbon::today()) {
                $validator->errors()->add('date', translate('messages.Start date must be greater than or equal to today'));
            }

            if ($endDate < $startDate) {
                $validator->errors()->add('date', translate('messages.End date must be greater than start date'));
            }

            // Custom validation: For video_promotion, ensure at least one video source is provided
            if ($this->advertisement_type === 'video_promotion') {
                $videoSource = $this->video_source ?? 'upload';

                // If upload source is selected, video_attachment must be present
                if ($videoSource === 'upload' && !$this->hasFile('video_attachment')) {
                    $validator->errors()->add('video_attachment', translate('messages.Please_upload_a_video_file'));
                }

                // If Instagram reel source is selected, instagram_reel_url must be present
                if ($videoSource === 'instagram_reel' && empty($this->instagram_reel_url)) {
                    $validator->errors()->add('instagram_reel_url', translate('messages.Please_select_an_Instagram_reel'));
                }

                // If Instagram URL source is selected, instagram_manual_url must be present
                if ($videoSource === 'instagram_url' && empty($this->instagram_manual_url)) {
                    $validator->errors()->add('instagram_manual_url', translate('messages.Please_enter_an_Instagram_reel_URL'));
                }
            }
        });
    }
    protected function failedValidation(Validator $validator)
    {
        $response = response()->json(['errors' => Helpers::error_processor($validator)]);
        throw new ValidationException($validator, $response);
    }
}
