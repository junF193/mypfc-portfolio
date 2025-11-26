# My PFC Manager

## 📖 概要
**My PFC Manager** は、日々の食事管理とPFCバランス（タンパク質・脂質・炭水化物）の最適化をサポートするWebアプリケーションです。
「何をどれくらい食べたか」を直感的に記録し、理想的な栄養バランスへの到達を支援します。

## 🛠 技術スタック

### Backend
- **Framework**: Laravel 10.x
- **Database**: SQLite (開発環境) / MySQL (本番環境想定)
- **Authentication**: Laravel Sanctum

### Frontend
- **Core**: Vanilla JavaScript (ES6+)
- **Component Framework**: Vue.js 3 (Options API)
- **Styling**: TailwindCSS
- **Build Tool**: Vite

## 💡 工夫した点：技術的課題の解決

### 1. フロントエンド：技術混在環境における状態同期
本プロジェクトのフロントエンドは、軽量なVanilla JSで構築された基盤（履歴リスト等）と、インタラクティブ性が求められる部分（お気に入りリスト）にVue.jsを導入した**ハイブリッド構成**となっています。

開発において直面した最大の課題は、**「Vanilla JS側でのデータ操作を、Vue.js側のコンポーネントにどうリアルタイムで反映させるか」**という点でした。

#### 課題：異なる技術間の「壁」
履歴リスト（Vanilla JS）から「お気に入り登録」を行った際、データベースへの保存は成功しても、画面右側にあるお気に入り一覧（Vue.js）には即座に反映されず、ページリロードが必要な状態でした。これは、VueのリアクティブシステムがVue管理外のDOMイベントを検知できないためです。

#### 解決策：CustomEventによるイベント駆動連携
この課題を解決するために、ブラウザ標準の `CustomEvent` を活用した疎結合な連携メカニズムを実装しました。

1.  **発火 (Publisher)**:
    Vanilla JS側でAPI通信が成功した直後に、操作内容に応じたカスタムイベント（`external-favorite-added` や `external-favorite-removed`）を `window` オブジェクトに対してディスパッチします。
2.  **購読 (Subscriber)**:
    Vueコンポーネントの `mounted` ライフサイクルメソッド内でこれらのイベントをリッスンします。
3.  **同期**:
    イベントを受け取ったVueコンポーネントが、ペイロード（追加されたアイテムや削除されたID）を自身の `data` プロパティに取り込むことで、リアクティブにDOMを更新します。

このアプローチにより、大規模なステート管理ライブラリ（Vuex/Pinia）を導入することなく、軽量かつ堅牢に技術間の同期ズレを解消しました。

### 2. バックエンド：SPA認証におけるミドルウェア構成の最適化
SPA開発において、Laravel Sanctumを用いたCookieベース認証がAPIリクエスト時に維持されず、401エラーが多発する問題に直面しました。

**【課題と分析】**
一般的な設定見直しでは解決しなかったため、デバッグ用ミドルウェア（TraceMiddleware）を一時的に導入し、リクエスト処理のパイプラインを可視化しました。
その結果、APIルートグループにおいて、Cookieの復号やセッション開始を担う `EncryptCookies` や `StartSession` ミドルウェアが実行されていないことが根本原因であると特定しました。

**【解決策】**
APIルートの設定を見直し、Sanctumのステートフル認証に必要なミドルウェア群（webグループ相当）が確実に適用されるようルーティング構成を再設計しました。
これにより、AIツールを単なる回答機としてではなく「思考の壁打ち相手」として活用し、ブラックボックスになりがちなフレームワーク内部の挙動を理解して解決するプロセスを経験しました。

## ✨ 主な機能
- **食事記録**: 日々の食事を検索・登録し、カロリーとPFCを自動計算。
- **ダッシュボード**: 当日の栄養摂取状況をグラフで可視化。
- **お気に入り管理**: よく食べるメニューをお気に入りに登録し、ワンタップで記録。
- **履歴からの登録**: 過去の食事履歴から素早く再登録。

## 🚀 セットアップ手順

```bash
# リポジトリのクローン
git clone [https://github.com/junF193/mypfc-portfolio]
cd my-pfc-manager

# 依存関係のインストール
composer install
npm install

# 環境設定
cp .env.example .env
php artisan key:generate

# データベースの準備（SQLiteを使用する場合）
touch database/database.sqlite
php artisan migrate

# サーバー起動
php artisan serve
npm run dev
```
