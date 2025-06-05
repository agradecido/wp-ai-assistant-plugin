# Add project specific ProGuard rules here.
# By default, the flags in this file are appended to flags specified
# in /tools/proguard/proguard-android.txt
# You can edit the include path and order by changing the proguardFiles
# directive in build.gradle.
#
# For more details, see
#   http://developer.android.com/guide/developing/tools/proguard.html

# Add any project specific keep rules here:

# If you use reflection or JNI strip out unneeded names
# -dontskipnonpubliclibraryclasses
# -dontskipnonpubliclibraryclassmembers

# If you are using Kotlin reflect, you need to add the following lines:
#-keep class kotlin.reflect.jvm.internal.** { *; }

# Keep `Parcelable` and `Serializable` classes
-keep class * implements android.os.Parcelable {
  public static final android.os.Parcelable$Creator *;
}
-keep class * implements java.io.Serializable { *; }

# Keep setters in data classes that are used with Gson
-keepclassmembers class * extends kotlin.coroutines.jvm.internal.SuspendLambda {
    <fields>;
    <methods>;
}

# Keep Room entities and DAOs
-keep class androidx.room.RoomDatabase { *; }
-keepclassmembers class * implements androidx.room.Dao { *; }
-keep @androidx.room.Entity class * { *; }
-keep @androidx.room.TypeConverters class * { *; }

# Keep classes and members annotated with @Keep for ProGuard
-keep @androidx.annotation.Keep class * {*;}
-keepclasseswithmembers class * {
    @androidx.annotation.Keep <fields>;
}
-keepclasseswithmembers class * {
    @androidx.annotation.Keep <methods>;
}
