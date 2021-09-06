<template>
  <div class="content-wrapper">
    <div class="flex w-full items-center">
      <div class="w-3/5">
        <div
          v-if="this.$page.frontmatter.helpfulVotes !== false"
          class="vote"
          :class="{ voted: hasVoted }"
        >
          <div class="pane options">
            <h4 class="heading block">
              {{
              hasVoted
              ? this.$themeConfig.feedback.thanks
              : this.$themeConfig.feedback.helpful
              }}
            </h4>
            <div class="vote-buttons inline-block">
              <button
                aria-label="Yes"
                class="option yes"
                :class="{ chosen: hasVoted && vote === true }"
                @click="handleFeedback(true)"
              >
                <ThumbUp />
              </button>
              <button
                aria-label="No"
                class="option no"
                :class="{ chosen: hasVoted && vote === false }"
                @click="handleFeedback(false)"
              >
                <ThumbDown />
              </button>
            </div>
            <a
              v-if="vote === false"
              class="more-feedback"
              :href="getIssueUrl()"
              target="_blank"
              rel="noopener"
            >{{ this.$themeConfig.feedback.more }}</a>
          </div>
        </div>
      </div>
      <div class="footer-links">
        <p>
          <PageEdit />
        </p>
        <p>
          <a href="https://binarcode.com/contact" target="_blank" rel="noopener">
            <span class="right-footer-icon">
              <Envelope />
            </span>
            contact us
          </a>
        </p>
        <p>
          <a href="https://restify.binarcode.com/" target="_blank" rel="noopener">
            <span class="right-footer-icon">
              <Reply />
            </span>
            back to restify.binarcode.com
          </a>
        </p>
        <div class="switch-wrapper block xl:hidden">
          <ColorModeSwitch v-on="$listeners" :on="isDark" />
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="postcss">
.vote {
  @apply block relative;

  h4 {
    @apply mx-0 my-0 py-0 select-none leading-none;
    -webkit-font-smoothing: antialiased;
  }

  .vote-buttons {
    @apply mt-3 select-none;
  }

  .pane {
    @apply block;
    transition: top 0.75s cubic-bezier(0.86, 0, 0.07, 1),
      opacity 0.5s cubic-bezier(0.86, 0, 0.07, 1);
    top: 0;

    &.options {
      top: 0;

      svg {
        @apply block relative;
      }
    }

    &.thanks {
      @apply opacity-0;
      top: 5rem;
    }
  }

  &.voted {
    .option {
      @apply text-slate opacity-25 border-slate pointer-events-none;

      &.chosen {
        @apply opacity-100;
      }
    }
  }

  .option {
    @apply relative inline-block overflow-hidden px-5 py-2;
    @apply bg-transparent rounded;
    @apply cursor-pointer;
    @apply fill-current text-center mr-3;
    @apply border text-blue;
    transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
    border-color: var(--border-color);

    &::before {
      @apply absolute z-0 top-0 left-0 bottom-0;
      content: " ";
      background: transparent;
      transition: opacity 0.5s cubic-bezier(0.19, 1, 0.22, 1);
    }

    &:focus {
      @apply outline-none;
    }

    &:active {
      transform: scale(0.97);
    }

    &:hover {
      @apply border-blue;
      svg {
        @apply opacity-100;
      }
    }

    svg {
      @apply opacity-75;
      transition: opacity 0.5s cubic-bezier(0.19, 1, 0.22, 1);
    }
  }
}

.more-feedback {
  @apply relative;
  top: -1px;
}

.footer-links {
  @apply w-2/5 text-sm text-right;

  a:hover .right-footer-icon {
    @apply opacity-100;
  }

  .right-footer-icon {
    @apply inline-block relative mr-1 text-light-slate opacity-25;
    top: 2px;
  }

  .switch-wrapper {
    @apply relative;
    right: -2px;
    top: -0.575rem;
  }

  .edit-link {
    @apply inline-block;
  }

  .page-edit {
    @apply py-0 overflow-auto mt-6;
  }
}
</style>

<script>
import { getStorage, setStorage } from "../Storage";
import ThumbUp from "../icons/ThumbUp";
import ThumbDown from "../icons/ThumbDown";
import Envelope from "../icons/Envelope";
import Reply from "../icons/Reply";
import ColorModeSwitch from "./ColorModeSwitch";
import PageEdit from "./PageEdit";

export default {
  components: {
    ThumbUp,
    ThumbDown,
    Envelope,
    Reply,
    ColorModeSwitch,
    PageEdit,
  },
  props: ["isDark"],
  data() {
    return {
      vote: null,
      hasVoted: null,
    };
  },
  mounted() {
    this.refreshState();
  },
  methods: {
    handleFeedback(wasHelpful) {
      if (typeof ga === "function") {
        ga(
          "send",
          "event",
          "HelpfulVote",
          wasHelpful ? "yes" : "no",
          "Vote",
          wasHelpful ? 1 : 0
        );
      } else {
        console.log(`Couldn’t log vote. :(`);
      }

      this.setVoteForPath(this.$route.fullPath, wasHelpful);
      this.hasVoted = true;
      this.vote = wasHelpful;
    },
    getStoredVotes() {
      let storedVotes = getStorage("votes", this.$site.base);

      if (storedVotes) {
        return JSON.parse(storedVotes);
      }

      return [];
    },
    getVoteForPath(path) {
      let votes = this.getStoredVotes();

      let votesForPath = votes.filter((item) => {
        return item.path === path;
      }, this);

      if (votesForPath.length) {
        return votesForPath[0].value;
      }

      return null;
    },
    setVoteForPath(path, vote) {
      let votes = this.getStoredVotes();
      votes.push({ path: path, value: vote });
      setStorage("votes", JSON.stringify(votes), this.$site.base);

      // force refresh
      this.refreshState();
    },
    refreshState() {
      this.vote = this.getVoteForPath(this.$route.fullPath);
      this.hasVoted = this.vote !== null;
    },
    getIssueUrl() {
      return encodeURI(
        `https://github.com/${this.$themeConfig.docsRepo}/issues/new?title=Improve “${this.$page.title}”&body=I have a suggestion for https://restify.binarcode.com/docs${this.$route.fullPath}:\n`
      );
    },
  },
  watch: {
    $route() {
      this.refreshState();
    },
  },
};
</script>
