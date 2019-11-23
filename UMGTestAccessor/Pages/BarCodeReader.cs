using System;
using System.Collections.Generic;
using ZXing;
using ZXing.Mobile;
using ZXing.Net.Mobile.Forms;
using Xamarin.Forms;
using UMGTestAccessor.API;
using UMGTestAccessor.Model;
using Newtonsoft.Json;
using Xamarin.Essentials;

namespace UMGTestAccessor.Pages
{
    public class BarCodeReader : ZXingScannerPage
    {
        public BarCodeReader():base(new MobileBarcodeScanningOptions()
        {
            PossibleFormats = new List<BarcodeFormat>() {
                BarcodeFormat.CODE_128,
                BarcodeFormat.CODE_39,
                BarcodeFormat.CODE_93
            }
        })
        {
            if (this.HasTorch)
            {
                this.IsTorchOn = true;
            }
            NavigationPage.SetHasNavigationBar(this, false);
            OnScanResult += ScanResult;
        }

        public async void ScanResult(Result result)
        {
            IsScanning = false;
            IsAnalyzing = false;

            Device.BeginInvokeOnMainThread(() => {
                Navigation.PopModalAsync();
                Navigation.PushModalAsync(new AlumnStatus(result.Text));
            });
            
        }

        protected override void OnAppearing()
        {
            base.OnAppearing();
            DeviceDisplay.KeepScreenOn = true;
        }
        protected override void OnDisappearing()
        {
            base.OnDisappearing();
            DeviceDisplay.KeepScreenOn = false;
        }
    }
}
