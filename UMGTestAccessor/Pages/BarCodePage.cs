using System;
using System.Collections.Generic;
using Xamarin.Forms;
using Xamarin.Essentials;
using ZXing.Net.Mobile.Forms;
using ZXing;
using ZXing.Mobile;

namespace UMGTestAccessor.Pages
{
    public class BarCodePage: ContentPage
    {
        public const int CENTER_HEGHT = 50;
        public const int CENTER_MAX_HEIGHT = 200;
        private static int focusSliderValue  = CENTER_HEGHT;

        private ZXingScannerView scanner;

        private int x = 0;
        private int y = 0;


        public BarCodePage()
        {
            NavigationPage.SetHasNavigationBar(this, false);
            BackgroundColor = Color.FromUint(AlumnStatus.COLOR_WORKING);
        }

        protected override void OnAppearing()
        {
            base.OnAppearing();
            SetScanner();
            StartScanning();
        }

        protected override void OnDisappearing()
        {
            base.OnDisappearing();
            StopScanning();
        }

        private void SetScanner()
        {

            y = (int)(Application.Current.MainPage.Height/2);
            x = (int)(Application.Current.MainPage.Width/2);


            scanner = new ZXingScannerView()
            {
                HorizontalOptions = LayoutOptions.FillAndExpand,
                VerticalOptions = LayoutOptions.FillAndExpand,
                IsTorchOn = true,
                IsEnabled = false
            };

            ZXingDefaultOverlay overlay = new ZXingDefaultOverlay();

            overlay.RowDefinitions[0].Height = new GridLength(2, GridUnitType.Star);
            overlay.RowDefinitions[1].Height = new GridLength(focusSliderValue, GridUnitType.Absolute);
            overlay.RowDefinitions[2].Height = new GridLength(2, GridUnitType.Star);
            /*
            Console.WriteLine(coll);

            Console.WriteLine("HasTorsh: " + scanner.HasTorch + ", IsTorshOn: " + scanner.IsTorchOn);
            scanner.ToggleTorch();
            Console.WriteLine("HasTorsh: " + scanner.HasTorch + ", IsTorshOn: " + scanner.IsTorchOn);

            *//*
            Slider focusSlider = new Slider(CENTER_HEGHT, CENTER_MAX_HEIGHT, focusSliderValue);
            focusSlider.ValueChanged += (s, a) =>
            {
                focusSliderValue = (int)a.NewValue;
                overlay.RowDefinitions[1].Height = new GridLength(a.NewValue, GridUnitType.Absolute);
            };*/

            Grid grid = new Grid()
            {
                VerticalOptions = LayoutOptions.FillAndExpand,
                HorizontalOptions = LayoutOptions.FillAndExpand,
                Children =
                {
                    scanner,
                    overlay,
                    //new Slider(30,200,30)
                    //focusSlider
                }
            };

            

            //Content = scanner;
            //Content = overlay;
            Content = grid;

            TapGestureRecognizer tap = new TapGestureRecognizer();
            tap.Tapped += AutoFocus;

            overlay.GestureRecognizers.Add(tap);

            scanner.Options = new MobileBarcodeScanningOptions()
            {
                PossibleFormats = new List<BarcodeFormat>
                {
                    BarcodeFormat.All_1D
                }
            };
            scanner.OnScanResult += OnScanResult;
        }

        private void AutoFocus(object sender, EventArgs args)
        {
            try {
                //scanner.AutoFocus(y, x);
                //scanner.AutoFocus();
                Console.WriteLine($"X: {x}, Y: {y}, x: {scanner.Width}, y: {scanner.Height}");
                scanner.AutoFocus(y, x);
            }
            catch(Exception e)
            {
                Console.WriteLine($"Error requesting AutoFocus to {X}, {Y}: "+e);
            }
        }

        private void OnScanResult(Result result)
        {
            Device.BeginInvokeOnMainThread(() => {
                StopScanning();
                Navigation.PushModalAsync(new AlumnStatus(result.Text));
            });
        }

        private void StopScanning()
        {
            DeviceDisplay.KeepScreenOn = false;
            scanner.IsAnalyzing = false;
            scanner.IsScanning = false;
        }

        private void StartScanning()
        {
            DeviceDisplay.KeepScreenOn = true;
            scanner.IsAnalyzing = true;
            scanner.IsScanning = true;
        }
    }
}
