# mTab新标签页

![logo](https://raw.githubusercontent.com/tsxcw/imagesHouse/itushan/mTabReadme/192.png)

### [mTab书签官网](https://mtab.cc) | [安装文档](https://mtab.cc/document.html)  | [作者Blog](https://blog.mcecy.com) | QQ群：694155153

![](https://raw.githubusercontent.com/tsxcw/imagesHouse/itushan/mTabReadme/1.png?x-image-process=image/resize,m_lfit,w_900)


### 主要有以下特点

跨设备同步：不再为了在不同设备上找不到书签或笔记而苦恼。Mtab书签让你的收藏网址和重要笔记在所有设备上同步。

跨浏览器支持：Mtab书签支持所有主流浏览器。Chrome、Firefox、Edge、Safari，无论你的选择是什么，都能在一应俱全的工具箱中找到你的书签和笔记。

多功能一体：Mtab书签不仅仅是一个书签工具，它还提供了一个实用的记事本功能，让你随时随地记录想法、灵感和待办事项。此外，它还内置了一些在线小工具，解决您的日常工作问题。

私有部署：如果部你对数据安全性有更高要求，Mtab书签也支持私有部署。你可以将它部署在自己的服务器上，完全掌控你的数据，不受任何干扰。

免费无广告：Mtab书签坚守“免费无广告”的原则，为用户提供清爽的使用体验，没有任何干扰。

Mtab书签的界面设计美观简洁，操作简单直观，让你可以专注于你的网络活动，而不是应用本身。它是你高效、无忧的网络生活的理想伴侣。
高效流畅的操作体验：超级简约却强大的操作逻辑，没有繁琐的操作流程即可处理复杂的事情。

## Demo演示站

#### **[演示站Demo入口](https://demo.mtab.cc)**

演示账号：admin

演示密码：123456


## Docker部署方式 (通过 GHCR)

**镜像名称**: `ghcr.io/crossgg/mtab:latest`

使用前可能需要登录 (如果该镜像在您的 Github 仓库中尚未转为 Public)：
```bash
docker login ghcr.io -u crossgg -p <您的_GITHUB_PAT>
```

直接拉取命令：
```bash
docker pull ghcr.io/crossgg/mtab:latest
```

部署命令： `docker run -itd --name mtab -p 9200:80 -v ./app:/app ghcr.io/crossgg/mtab:latest`

命令解释： 其中 9200 可改为你服务器的另外端口。 /opt/mtab 是挂载路径，容器内目录和端口必须是 80 和 /app，--name为自定义容器名称。

可选挂载说明：对于我们新增的 SQLite 架构支持，您可以多映射一个宿主机持久化目录到 `/app/data`，例如加一个 `-v /opt/mtab-data:/app/data`，这可以实现安全平滑的迁移。

程序数据库安装： 部署完docker后访问您设置的端口，然后填写一些数据库配置后点击 安装 按钮即可等待安装完成， 注意的是容器部署下数据库地址请不要填写127.0.0.1,因为容器内127.0.0.1不指向宿主机网络。

最后事项： 最后如果要使用外网访问，为了安全请使用Nginx反向代理或者CDN来代理您创建时填写的端口，并且配置SSL证书启用HTTPS，纯内网环境请随意啦。

### docker-compose.yml

在你想安装的目录创建docker-compose.yml，然后安装的目录执行`docker-compose  up -d `即可

```yml
version: '3'
services:
  mtabServer:
    image: ghcr.io/crossgg/mtab:latest
    container_name: mtabServer
    user: "${USER_ID}:${GROUP_ID}"
    ports:
      - "9200:80"
    volumes:
      - ./:/app
    #  - ./data:/app/data
    restart: unless-stopped
```
## 预览图

![](https://raw.githubusercontent.com/tsxcw/imagesHouse/itushan/mTabReadme/1.png)
<img src="https://raw.githubusercontent.com/tsxcw/imagesHouse/itushan/mTabReadme/2.png" width="50%"><img src="https://raw.githubusercontent.com/tsxcw/imagesHouse/itushan/mTabReadme/3.png" width="50%">
<img src="https://raw.githubusercontent.com/tsxcw/imagesHouse/itushan/mTabReadme/4.png" width="33.3%"><img src="https://raw.githubusercontent.com/tsxcw/imagesHouse/itushan/mTabReadme/5.png" width="33.3%"><img src="https://raw.githubusercontent.com/tsxcw/imagesHouse/itushan/mTabReadme/6.png" width="33.3%">
<img src="https://raw.githubusercontent.com/tsxcw/imagesHouse/itushan/mTabReadme/8.png" width="50%"><img src="https://raw.githubusercontent.com/tsxcw/imagesHouse/itushan/mTabReadme/7.png" width="50%">

