using System;
using Xamarin.Forms;
using System.Collections.Generic;
using System.ComponentModel;
using System.Runtime.CompilerServices;
using System.Windows.Input;
using ZXing;
using ZXing.Mobile;
using ZXing.Net.Mobile.Forms;
using UMGTestAccessor.API;
using UMGTestAccessor.Model;


namespace UMGTestAccessor.ViewModels
{
    public class BarCodeReaderPageViewModel: INotifyPropertyChanged
    {
        private string _result;
        public string Result
        {
            get => _result;
            set
            {
                _result = value;
                OnPropertyChanged(nameof(Result));
            }
        }
        public ICommand ButtonCommand { get; private set; }

        public BarCodeReaderPageViewModel()
        {
            ButtonCommand = new Command(OnButtomCommand);
        }
        public event PropertyChangedEventHandler PropertyChanged;

        private void OnPropertyChanged([CallerMemberName] string propertyName = "")
        {
            PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(propertyName));
        }

        private void OnButtomCommand()
        {
            var options = new MobileBarcodeScanningOptions();
            options.PossibleFormats = new List<BarcodeFormat>
            {
                BarcodeFormat.CODE_128,
                BarcodeFormat.CODE_39,
                BarcodeFormat.CODE_93
            };
            var page = new ZXingScannerPage(options) { Title = "Scanner" };
            var closeItem = new ToolbarItem { Text = "Close" };
            closeItem.Clicked += (object sender, EventArgs e) =>
            {
                page.IsScanning = false;
                Device.BeginInvokeOnMainThread(() =>
                {
                    Application.Current.MainPage.Navigation.PopModalAsync();
                });
            };
            page.ToolbarItems.Add(closeItem);
            page.OnScanResult += (result) =>
            {
                page.IsScanning = false;

                Device.BeginInvokeOnMainThread(() => {
                    Application.Current.MainPage.Navigation.PopModalAsync();
                    if (string.IsNullOrEmpty(result.Text))
                    {
                        Result = "No valid code has been scanned";
                    }
                    else
                    {
                        Result = $"Result: {result.Text}";


                        GetAlumn(result.Text);

                    }
                });
            };
            Application.Current.MainPage.Navigation.PushModalAsync(new NavigationPage(page) { BarTextColor = Color.White, BarBackgroundColor = Color.CadetBlue }, true);
        }

        private async void GetAlumn(string code)
        {

            GZCoreResponce<Alumn> alumn = await GZCore.Instance.Get<Alumn>("_Auth/canTakeExam",new Dictionary<string, string>() {
                { "code",code },
                { "uuid", Guid.NewGuid().ToString() } });

            Console.WriteLine( Newtonsoft.Json.JsonConvert.SerializeObject(alumn));
        }
    }
}
