using System;
namespace UMGTestAccessor.IO
{
    public interface ILocalFileWriter
    {
        void Write(string fileName, string content);
        string Read(string fileName);
        bool Exists(string fileName);
    }
}
