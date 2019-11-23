using System;
using System.Threading.Tasks;
using Xamarin.Forms;
using ZXing.Rendering;
using ZXing.Net.Mobile.Android;
using ZXing.Net.Mobile.Forms.Android;
using Xamarin.Forms.Platform.Android;
using ZXing.Net.Mobile.Forms;
using Android.Hardware;

[assembly: ExportRenderer(typeof(ZXingScannerView), typeof(UMGTestAccessor.Droid.Renderers.UMGZXingScannerViewRenderer))]
namespace UMGTestAccessor.Droid.Renderers
{
    public class UMGZXingScannerViewRenderer : ZXingScannerViewRenderer
    {
        protected override void OnElementChanged(ElementChangedEventArgs<ZXingScannerView> e)
        {
            e.NewElement.IsTorchOn = true;
            base.OnElementChanged(e);
            this.Control.Torch(true);
            
            
            /*
            Task.Run(()=>{
                Task.Delay(30000);
                Device.BeginInvokeOnMainThread(()=>{
                    this.Control.AutoFocus(this.Control.Width/2,this.Control.Height/2);
                });
            
                });*/

        }
    }
}
